<?php

declare(strict_types=1);

namespace App\Services\Kafka;

use Illuminate\Support\Facades\Log;
use RdKafka\Conf;
use RdKafka\Producer;

/**
 * Отправка событий в Kafka.
 *
 * Kafka — side-channel: сбой брокера не должен ронять бизнес-сценарий,
 * поэтому ошибки логируются, а produce() возвращает bool.
 *
 * Продюсер создаётся лениво: пока kafka.enabled=false, объекты ext-rdkafka
 * не трогаются вовсе, поэтому код собирается и исполняется и без расширения
 * (CI), и при выключенном брокере (тесты).
 */
final class KafkaService
{
    private ?Producer $producer = null;

    /**
     * Опубликовать событие в топик.
     *
     * @param  array<string, mixed>  $payload
     */
    public function produce(string $topic, array $payload): bool
    {
        if (! config('kafka.enabled', true)) {
            return false;
        }

        try {
            $message = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $this->producer()
                ->newTopic($topic)
                ->producev(RD_KAFKA_PARTITION_UA, 0, $message);

            $err = $this->producer()->flush((int) config('kafka.producer.flush_timeout_ms'));

            if ($err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                Log::warning('Kafka: flush не дождался доставки', [
                    'topic' => $topic,
                    'err' => rd_kafka_err2name($err),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Kafka: produce не выполнен', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Продюсер создаётся при первом реальном produce(): до этого расширение
     * не требуется. Экземпляр переиспользуется (singleton сервиса).
     */
    private function producer(): Producer
    {
        if ($this->producer === null) {
            $conf = new Conf;
            $conf->set('bootstrap.servers', (string) config('kafka.brokers'));
            $conf->set('client.id', (string) config('kafka.client_id'));

            $this->producer = new Producer($conf);
        }

        return $this->producer;
    }
}
