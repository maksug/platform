<script setup lang="ts">
interface Project {
  id: number
  title: string
  description: string
  tags: string[]
  url: string
}

const projects: Project[] = [
  {
    id: 1,
    title: 'Проект 1',
    description: 'Описание проекта — заглушка.',
    tags: ['Vue', 'TypeScript', 'Vite'],
    url: '#',
  },
  {
    id: 2,
    title: 'Проект 2',
    description: 'Описание проекта — заглушка.',
    tags: ['Laravel', 'PHP', 'PostgreSQL'],
    url: '#',
  },
  {
    id: 3,
    title: 'Проект 3',
    description: 'Описание проекта — заглушка.',
    tags: ['Docker', 'Kafka', 'DevOps'],
    url: '#',
  },
]
</script>

<template>
  <header class="header">
    <div class="container">
      <a href="/" class="logo">Portfolio</a>
      <nav class="nav">
        <a href="#projects">Проекты</a>
        <a href="#contact">Контакты</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="container">
        <h1>Привет, я — Имя Фамилия</h1>
        <p class="hero__sub">Fullstack-разработчик</p>
        <p class="hero__desc">
          Создаю веб-приложения с чёткой архитектурой, типизацией и
          автоматизированным тестированием.
        </p>
      </div>
    </section>

    <section id="projects" class="projects">
      <div class="container">
        <h2 class="section-title">Проекты</h2>

        <div class="projects__grid">
          <article v-for="p in projects" :key="p.id" class="card">
            <h3 class="card__title">{{ p.title }}</h3>
            <p class="card__desc">{{ p.description }}</p>
            <ul class="card__tags">
              <li v-for="tag in p.tags" :key="tag" class="tag">{{ tag }}</li>
            </ul>
          </article>
        </div>
      </div>
    </section>

    <section id="contact" class="contact">
      <div class="container">
        <h2 class="section-title">Контакты</h2>
        <p class="contact__text">
          <a href="mailto:example@mail.com">example@mail.com</a>
        </p>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container">
      <p>&copy; {{ new Date().getFullYear() }} Portfolio</p>
    </div>
  </footer>
</template>

<style scoped>
.header {
  padding: 1rem 0;
  border-bottom: 1px solid var(--border);
}

.header .container {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--fg);
  text-decoration: none;
}

.nav a {
  margin-left: 1.5rem;
  color: var(--fg-muted);
  text-decoration: none;
}

.nav a:hover {
  color: var(--fg);
}

.hero {
  padding: 4rem 0;
}

.hero h1 {
  font-size: 2.5rem;
  margin: 0 0 0.5rem;
  line-height: 1.2;
}

.hero__sub {
  font-size: 1.25rem;
  color: var(--fg-muted);
  margin: 0 0 1rem;
}

.hero__desc {
  max-width: 60ch;
  color: var(--fg-muted);
  line-height: 1.6;
}

.projects {
  padding: 3rem 0;
}

.section-title {
  font-size: 1.75rem;
  margin: 0 0 2rem;
}

.projects__grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.card {
  padding: 1.5rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--bg);
}

.card__title {
  margin: 0 0 0.5rem;
}

.card__desc {
  color: var(--fg-muted);
  font-size: 0.9rem;
  line-height: 1.5;
  margin: 0 0 1rem;
}

.card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  list-style: none;
  margin: 0;
  padding: 0;
}

.tag {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  background: var(--bg-muted);
  border-radius: 4px;
  color: var(--fg-muted);
}

.contact {
  padding: 3rem 0;
  border-top: 1px solid var(--border);
}

.contact__text {
  font-size: 1.1rem;
}

.contact__text a {
  color: var(--accent);
  text-decoration: none;
}

.contact__text a:hover {
  text-decoration: underline;
}

.footer {
  padding: 2rem 0;
  border-top: 1px solid var(--border);
  color: var(--fg-muted);
  font-size: 0.85rem;
}
</style>
