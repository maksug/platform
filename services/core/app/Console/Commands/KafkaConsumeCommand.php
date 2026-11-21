<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

/**
 * Консумер Kafka: читает сообщения из топика и логирует.
 *
 * Примеры:
 *   php artisan kafka:consume user-events
 *   php artisan kafka:consume user-events --limit=10
 *   php artisan kafka:consume user-events --once --timeout=30
 */
final class KafkaConsumeCommand extends Command
{
    protected $signature = 'kafka:consume
                            {topic : Топик Kafka}
                            {--limit=0 : Количество сообщений (0 = бесконечно)}
                            {--timeout=0 : Максимум секунд ожидания сообщений (0 = ждать вечно)}
                            {--once : Прочитать одно сообщение и выйти}
                            {--reset : Прочитать топик с начала (manual assign, оффсеты группы не сдвигаются)}';

    protected $description = 'Консумер Kafka: читает сообщения и логирует в Laravel';

    /**
     * Выполнить команду.
     */
    public function handle(): int
    {
        $topic = (string) $this->argument('topic');
        $limit = (int) $this->option('limit');
        $timeout = (int) $this->option('timeout');
        $once = $this->option('once');
        $reset = $this->option('reset');

        if ($once) {
            $limit = 1;
        }

        $this->info("Начинаем консум: topic={$topic} limit={$limit} timeout={$timeout}");

        $conf = new Conf;
        $conf->set('group.id', (string) config('kafka.consumer.group_id'));
        $conf->set('bootstrap.servers', (string) config('kafka.brokers'));
        $conf->set('auto.offset.reset', (string) config('kafka.consumer.offset_reset', 'earliest'));

        $pollMs = (int) config('kafka.consumer.poll_interval_ms');
        $deadline = $timeout > 0 ? time() + $timeout : null;
        $count = 0;

        if ($reset) {
            // "--reset": читаем топик с начала через временную consumer-группу.
            // Новая группа + auto.offset.reset=earliest => стартуем с offset 0,
            // реальная группа foundation-core не трогается.
            $groupId = (string) config('kafka.consumer.group_id').'-reset-'.getmypid();
            $conf->set('group.id', $groupId);
            $this->info("Сброс позиции: временная группа {$groupId}, читаем с начала");
        }

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe([$topic]);

        while (true) {
            if ($limit > 0 && $count >= $limit) {
                $this->info("Достигнут лимит ({$limit} сообщений), завершаем");
                break;
            }

            // consume() блокируется на $pollMs — отдельный sleep в ветках ожидания не нужен
            $message = $consumer->consume($pollMs);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $count++;
                    $payload = (string) $message->payload;
                    $this->line("  #{$count} [{$message->topic_name}] key={$message->key}: {$payload}");
                    $consumer->commit();
                    break;

                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    // Новых сообщений нет — проверяем предельное время ожидания
                    if ($deadline !== null && time() >= $deadline) {
                        $this->warn("Сообщений не появилось за {$timeout}с, завершаем");

                        break 2;
                    }
                    break;

                default:
                    Log::error('Kafka consumer error', [
                        'topic' => $topic,
                        'err' => rd_kafka_err2name($message->err),
                        'details' => $message->err,
                    ]);

                    if ($deadline !== null && time() >= $deadline) {
                        break 2;
                    }
                    break;
            }
        }

        $consumer->close();
        $this->info("Готово: консумировано {$count} сообщений");

        return Command::SUCCESS;
    }
}
