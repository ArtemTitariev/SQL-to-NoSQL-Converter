<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&family=Source+Sans+Pro:wght@400;700&family=Lora:wght@400;700&family=Nunito:wght@400;700&display=swap" rel="stylesheet">


    {{-- <link href="/path/to/your/tailwind.css" rel="stylesheet"> --}}
    <title>Custom Tailwind Theme</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-light text-dark">
    <header class="bg-primary text-light p-4">
        <h1 class="text-2xl">Заголовок primary</h1>
    </header>
    <main class="p-4">
        <section class="mb-4">
            <h2 class="text-light bg-secondary">secondary</h2>
            <p class="text-dark">Це приклад тексту secondary з використанням кастомної теми Tailwind CSS.</p>
        </section>
        <section class="mb-4">
            <h2 class="text-light bg-accent font-serif">accent serif</h2>
            <p class="text-dark">Ще один приклад тексту з іншими кольорами та шрифтами.</p>
        </section>
        <section class="mb-4">
            <h2 class="text-dark bg-accent">dark + accent</h2>
        </section>
        <section class="mb-4">
            <p class="bg-success">success</p>
        </section>
        <section class="mb-4">
            <p class="bg-danger">danger</p>
        </section>
        <section class="mb-4">
            <p class="bg-warning">warning</p>
        </section>
        <section class="mb-4">
            <p class="bg-info">info</p>
        </section>
        <section class="mb-4">
            <p class="bg-gray text-light">gray</p>
        </section>
    </main>
    <footer class="bg-dark text-light p-4">
        <p>© 2024 Вебдодаток. Всі права захищені.</p>
    </footer>
</body>

</html>
