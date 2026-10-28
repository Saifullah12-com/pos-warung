# POS Warung Djaniman - Fase 1

Laravel 11/12 + MySQL + Blade + Tailwind. Salin isi folder ini ke proyek Laravel baru
(lihat panduan di chat), lalu:

    composer install && npm install
    cp .env.example .env && php artisan key:generate   # (otomatis bila dibuat lewat create-project)
    php artisan migrate:fresh --seed
    php artisan storage:link
    npm run build        # atau: npm run dev
    php artisan serve

Login awal: admin / password  dan  kasir1 / password  (GANTI sebelum dipakai sungguhan).
Tes: php artisan test
