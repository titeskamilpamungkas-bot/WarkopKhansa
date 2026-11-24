/**
 * Interaksi utama WARKOP KHANSA.
 * Semua perilaku diatur lewat atribut data-* di view, sehingga tidak ada kode PHP di dalam JS.
 *
 *  data-modal-open="#id"      buka modal
 *  data-modal-close           tutup modal (di dalam modal)
 *  data-action="url"          isi action form di dalam modal
 *  data-fill='{"nama":".."}'  isi input form berdasarkan atribut name
 *  data-title / data-title-target  ubah judul modal
 *  form[data-confirm]         minta konfirmasi sebelum kirim
 *  data-toggle-password="#id" tampilkan / sembunyikan password
 *  data-print                 cetak halaman
 *  data-count / data-format   angka yang naik perlahan (format: rupiah)
 *  canvas[data-chart]         grafik Chart.js dengan konfigurasi JSON
 */
(function () {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const desktop = function () { return window.matchMedia('(min-width: 961px)').matches; };

    // ---- Jam dan tanggal (zona waktu WIB) ----
    function perbaruiJam() {
        const sekarang = new Date();
        const jam = document.getElementById('jamSekarang');
        const tgl = document.getElementById('tanggalSekarang');

        if (jam) {
            jam.textContent = sekarang.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour12: false });
        }
        if (tgl) {
            tgl.textContent = sekarang.toLocaleDateString('id-ID', {
                timeZone: 'Asia/Jakarta', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
        }
    }
    perbaruiJam();
    setInterval(perbaruiJam, 1000);

    // ---- Sidebar: sembunyi di layar lebar, geser di layar kecil ----
    const layout = document.getElementById('appLayout');
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');

    if (layout && sidebar && menuToggle) {
        try {
            if (desktop() && localStorage.getItem('sidebarCollapsed') === '1') {
                layout.classList.add('collapsed');
            }
        } catch (e) { /* penyimpanan diblokir: abaikan */ }

        menuToggle.addEventListener('click', function () {
            if (desktop()) {
                layout.classList.toggle('collapsed');
                try {
                    localStorage.setItem('sidebarCollapsed', layout.classList.contains('collapsed') ? '1' : '0');
                } catch (e) { /* abaikan */ }
            } else {
                sidebar.classList.toggle('open');
            }
        });

        document.addEventListener('click', function (e) {
            if (!desktop() && sidebar.classList.contains('open')
                && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ---- Modal ----
    function bukaModal(modal, pemicu) {
        const form = modal.querySelector('form');

        if (form) {
            form.reset();

            if (pemicu.dataset.action) {
                form.action = pemicu.dataset.action;
            }

            if (pemicu.dataset.fill) {
                let data = {};
                try { data = JSON.parse(pemicu.dataset.fill); } catch (e) { data = {}; }

                Object.keys(data).forEach(function (nama) {
                    const input = form.elements.namedItem(nama);
                    if (input) {
                        input.value = data[nama];
                    }
                });
            }
        }

        if (pemicu.dataset.title && pemicu.dataset.titleTarget) {
            const judul = document.querySelector(pemicu.dataset.titleTarget);
            if (judul) judul.textContent = pemicu.dataset.title;
        }

        modal.classList.remove('hidden');

        const fokus = modal.querySelector('input:not([type="hidden"]), select');
        if (fokus) fokus.focus();
    }

    document.addEventListener('click', function (e) {
        const buka = e.target.closest('[data-modal-open]');
        if (buka) {
            const modal = document.querySelector(buka.dataset.modalOpen);
            if (modal) bukaModal(modal, buka);
            return;
        }

        const tutup = e.target.closest('[data-modal-close]');
        if (tutup) {
            const overlay = tutup.closest('.modal-overlay');
            if (overlay) overlay.classList.add('hidden');
            return;
        }

        if (e.target.classList && e.target.classList.contains('modal-overlay')) {
            e.target.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(function (m) {
                m.classList.add('hidden');
            });
        }
    });

    // ---- Konfirmasi sebelum hapus ----
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // ---- Tampilkan / sembunyikan password ----
    document.querySelectorAll('[data-toggle-password]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            const input = document.querySelector(tombol.dataset.togglePassword);
            if (!input) return;

            const tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';

            const ikon = tombol.querySelector('i');
            if (ikon) {
                ikon.className = tampil ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
            }
        });
    });

    // ---- Cetak ----
    document.querySelectorAll('[data-print]').forEach(function (tombol) {
        tombol.addEventListener('click', function () { window.print(); });
    });

    // ---- Notifikasi (flash message diubah menjadi toast) ----
    function tampilToast(tipe, pesan) {
        let wrap = document.querySelector('.toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'toast-wrap';
            document.body.appendChild(wrap);
        }

        const toast = document.createElement('div');
        toast.className = 'toast' + (tipe === 'success' ? ' success' : (tipe === 'warning' ? ' warning' : ''));
        toast.setAttribute('role', 'status');

        const ikon = document.createElement('i');
        ikon.className = 'fa-solid ' + (tipe === 'success' ? 'fa-circle-check' : (tipe === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'));

        const teks = document.createElement('span');
        teks.textContent = pesan;

        const tutup = document.createElement('button');
        tutup.type = 'button';
        tutup.setAttribute('aria-label', 'Tutup notifikasi');
        tutup.innerHTML = '<i class="fa-solid fa-xmark"></i>';

        toast.append(ikon, teks, tutup);
        wrap.appendChild(toast);

        const hapus = function () {
            toast.classList.add('leave');
            setTimeout(function () { toast.remove(); }, 300);
        };
        tutup.addEventListener('click', hapus);
        setTimeout(hapus, 4500);
    }

    document.querySelectorAll('.content > .alert').forEach(function (alert) {
        let tipe = 'danger';
        if (alert.classList.contains('alert-success')) tipe = 'success';
        else if (alert.classList.contains('alert-warning')) tipe = 'warning';

        tampilToast(tipe, alert.textContent.trim());
        alert.remove();
    });

    // ---- Animasi kemunculan saat di-scroll ----
    const elemenReveal = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !reduceMotion) {
        const pengamat = new IntersectionObserver(function (entri) {
            entri.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.classList.add('in');
                    pengamat.unobserve(en.target);
                }
            });
        }, { threshold: 0.12 });
        elemenReveal.forEach(function (el) { pengamat.observe(el); });
    } else {
        elemenReveal.forEach(function (el) { el.classList.add('in'); });
    }

    // ---- Angka statistik naik perlahan ----
    function formatAngka(nilai, format) {
        const bulat = Math.round(nilai).toLocaleString('id-ID');
        return format === 'rupiah' ? 'Rp ' + bulat : bulat;
    }

    function hitungAngka(el) {
        const target = Number(el.dataset.count) || 0;
        const format = el.dataset.format || '';

        if (reduceMotion) {
            el.textContent = formatAngka(target, format);
            return;
        }

        const durasi = 900;
        const mulai = performance.now();

        function langkah(sekarang) {
            const t = Math.min((sekarang - mulai) / durasi, 1);
            const eased = 1 - Math.pow(1 - t, 3);
            el.textContent = formatAngka(target * eased, format);
            if (t < 1) requestAnimationFrame(langkah);
        }
        requestAnimationFrame(langkah);
    }

    const elemenAngka = document.querySelectorAll('[data-count]');
    if ('IntersectionObserver' in window) {
        const pengamatAngka = new IntersectionObserver(function (entri) {
            entri.forEach(function (en) {
                if (en.isIntersecting) {
                    hitungAngka(en.target);
                    pengamatAngka.unobserve(en.target);
                }
            });
        }, { threshold: 0.5 });
        elemenAngka.forEach(function (el) { pengamatAngka.observe(el); });
    } else {
        elemenAngka.forEach(hitungAngka);
    }

    // ---- Efek riak saat tombol diklik ----
    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('.btn, .btn-icon, .qty-btn');
        if (!tombol || tombol.disabled || reduceMotion) return;

        const riak = document.createElement('span');
        const rect = tombol.getBoundingClientRect();
        const ukuran = Math.max(rect.width, rect.height);

        riak.className = 'ripple';
        riak.style.width = riak.style.height = ukuran + 'px';
        riak.style.left = (e.clientX - rect.left - ukuran / 2) + 'px';
        riak.style.top = (e.clientY - rect.top - ukuran / 2) + 'px';
        tombol.appendChild(riak);
        setTimeout(function () { riak.remove(); }, 600);
    });

    // ---- Kartu statistik ikut miring mengikuti kursor (perangkat dengan hover) ----
    if (window.matchMedia('(hover: hover)').matches && !reduceMotion) {
        document.querySelectorAll('.stat-card').forEach(function (kartu) {
            kartu.addEventListener('pointermove', function (e) {
                const r = kartu.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width - 0.5;
                const y = (e.clientY - r.top) / r.height - 0.5;
                kartu.style.transform = 'perspective(800px) rotateX(' + (-y * 6).toFixed(2)
                    + 'deg) rotateY(' + (x * 6).toFixed(2) + 'deg) translateY(-3px)';
            });
            kartu.addEventListener('pointerleave', function () {
                kartu.style.transform = '';
            });
        });
    }

    // ---- Grafik Chart.js ----
    if (typeof Chart !== 'undefined') {
        const merah = '#c8102e';

        document.querySelectorAll('canvas[data-chart]').forEach(function (canvas) {
            let cfg;
            try { cfg = JSON.parse(canvas.dataset.chart); } catch (e) { return; }

            const batang = cfg.type === 'bar';

            new Chart(canvas, {
                type: batang ? 'bar' : 'line',
                data: {
                    labels: cfg.labels || [],
                    datasets: [{
                        label: cfg.label || 'Nilai',
                        data: cfg.data || [],
                        borderColor: merah,
                        backgroundColor: batang ? merah : 'rgba(200, 16, 46, 0.15)',
                        pointBackgroundColor: merah,
                        pointRadius: 4,
                        fill: !batang,
                        tension: 0.35,
                        borderRadius: 8,
                        maxBarThickness: 42
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: reduceMotion ? 0 : 900, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return 'Rp ' + Number(ctx.parsed.y).toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) { return 'Rp ' + Number(v).toLocaleString('id-ID'); }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
    }

    // ---- Halaman login ----
    const formLogin = document.getElementById('formLogin');
    if (formLogin) {
        formLogin.addEventListener('submit', function (e) {
            if (!formLogin.checkValidity()) {
                e.preventDefault();
                formLogin.reportValidity();
                return;
            }
            const tombolLogin = formLogin.querySelector('.btn-login');
            if (tombolLogin) {
                tombolLogin.classList.add('loading');
                tombolLogin.disabled = true;
            }
        });

        const kartuLogin = document.getElementById('loginCard');
        if (kartuLogin && kartuLogin.querySelector('.alert-danger')) {
            kartuLogin.classList.add('shake');
        }
    }

    // Cahaya di halaman login mengikuti kursor
    if (document.body.classList.contains('login-body') && !reduceMotion) {
        document.addEventListener('pointermove', function (e) {
            document.body.style.setProperty('--mx', e.clientX + 'px');
            document.body.style.setProperty('--my', e.clientY + 'px');
        });
    }
})();
