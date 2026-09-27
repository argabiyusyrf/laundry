/* ============================================================
   LaundryKu — JavaScript pendukung (offline, tanpa CDN)
   ============================================================ */

(function () {
  'use strict';

  /* ================= Alamat dasar =================
     Semua alamat yang dibentuk JavaScript harus lewat sini. Nilai LK_BASE
     dikirim server di partials/header.php; bila halaman tidak mengirimnya
     (mis. halaman tanpa header), memakai path relatif tetap aman. */
  var LK_BASE = typeof window.LK_BASE === 'string' ? window.LK_BASE.replace(/\/+$/, '') : '';

  function lkUrl(rute) {
    return LK_BASE + '/index.php?r=' + rute;
  }

  /* ================= Sidebar responsif ================= */
  var body = document.body;
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebarOverlay');
  var btnMenu = document.getElementById('btnMenu');
  var btnClose = document.getElementById('btnCloseSidebar');

  function isMobile() {
    return window.matchMedia('(max-width: 860px)').matches;
  }
  function setAria(open) {
    if (btnMenu) btnMenu.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  function openSidebar() { body.classList.add('sidebar-open'); setAria(true); }
  function closeSidebar() { body.classList.remove('sidebar-open'); setAria(false); }

  function toggleSidebar() {
    if (sidebar && isMobile()) {
      body.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
    } else if (sidebar) {
      var collapsed = body.classList.toggle('sidebar-collapsed');
      try { localStorage.setItem('lk-sidebar', collapsed ? '1' : '0'); } catch (e) {}
    }
  }

  if (btnMenu) {
    btnMenu.addEventListener('click', toggleSidebar);
  }
  if (btnClose) {
    btnClose.addEventListener('click', function () {
      if (isMobile()) closeSidebar(); else toggleSidebar();
    });
  }
  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });
  if (sidebar) {
    // Tutup otomatis saat pindah halaman (mobile)
    sidebar.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        if (isMobile()) closeSidebar();
      });
    });
  }

  // Pulihkan mode collapsed di desktop (persist per perangkat)
  if (!isMobile()) {
    try {
      if (localStorage.getItem('lk-sidebar') === '1') {
        body.classList.add('sidebar-collapsed');
      }
    } catch (e) {}
  }

  /* ================= Dropdown user (sidebar-foot) ================= */
  var userDD = document.getElementById('userDropdown');
  var btnUser = document.getElementById('btnUserMenu');
  if (userDD && btnUser) {
    function openUserMenu() {
      userDD.classList.add('open');
      btnUser.setAttribute('aria-expanded', 'true');
    }
    function closeUserMenu() {
      userDD.classList.remove('open');
      btnUser.setAttribute('aria-expanded', 'false');
    }
    btnUser.addEventListener('click', function (e) {
      e.stopPropagation();
      userDD.classList.contains('open') ? closeUserMenu() : openUserMenu();
    });
    document.addEventListener('click', function (e) {
      if (!userDD.contains(e.target)) closeUserMenu();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeUserMenu();
    });
  }

  /* Sync status aria tombol menu dengan ukuran layar */
  var syncAria = function () {
    if (btnMenu) btnMenu.setAttribute('aria-expanded', 'false');
  };
  window.addEventListener('resize', syncAria);
  var mq = window.matchMedia('(max-width: 860px)');
  if (mq.addEventListener) mq.addEventListener('change', syncAria);

  /* ================= Konfirmasi hapus ================= */
  document.querySelectorAll('form.js-hapus').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      /* Pesan bisa diganti lewat data-pesan pada form/button. */
      var pesan = f.getAttribute('data-pesan') || 'Yakin ingin menghapus data ini? Tindakan tidak bisa dibatalkan.';
      if (!confirm(pesan)) {
        ev.preventDefault();
      }
    });
  });

  /* ================= Konfirmasi ubah status ================= */
  document.querySelectorAll('form.js-status').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!confirm('Ubah status transaksi ini?')) {
        ev.preventDefault();
      }
    });
    /* Daftar transaksi memakai <select> yang langsung mengirim formulir.
       HTMLFormElement.submit() tidak melewati event "submit", jadi konfirmasi
       di atas tidak akan jalan. Karena itu perubahan select ditangani di sini
       dan memakai requestSubmit() agar event submit tetap terjadi. */
    var pilih = f.querySelector('select[name="status"]');
    if (pilih) {
      pilih.addEventListener('change', function () {
        var label = pilih.options[pilih.selectedIndex] ? pilih.options[pilih.selectedIndex].text : '';
        if (!confirm('Ubah status nota ini menjadi "' + label + '"?')) {
          pilih.selectedIndex = pilih.dataset.lama ? parseInt(pilih.dataset.lama, 10) : pilih.selectedIndex;
          return;
        }
        if (typeof f.requestSubmit === 'function') {
          f.requestSubmit();
        } else {
          f.submit();
        }
      });
      pilih.dataset.lama = pilih.selectedIndex;
    }
  });

  /* ================= Cegah kirim ganda =================
     Klik cepat atau koneksi lambat bisa membuat satu form terkirim dua kali.
     Untuk nota laundry ini berbahaya: nomor nota dibuat dari MAX(no_nota)+1
     sehingga request kedua gagal dan pengguna kehilangan inputnya. */
  document.querySelectorAll('form.js-kirim').forEach(function (f) {
    f.addEventListener('submit', function () {
      var tombol = f.querySelector('button[type="submit"]');
      if (tombol) {
        tombol.disabled = true;
        /* Buka lagi bila browser menahan halaman (mis. validasi HTML5 gagal). */
        setTimeout(function () { tombol.disabled = false; }, 8000);
      }
    });
  });

  /* ================= Cetak otomatis pada halaman nota ================= */
  if (document.body.getAttribute('data-cetak') === '1') {
    window.addEventListener('load', function () {
      setTimeout(function () { window.print(); }, 400);
    });
  }

  /* ================= Form transaksi dinamis ================= */
  var baris = document.getElementById('barisPaket');
  var btnTambah = document.getElementById('btnTambahBaris');
  var tmpl = document.getElementById('tmplBaris');
  if (baris && btnTambah && tmpl) {

    var formTrx = document.getElementById('formTransaksi');
    var satuan = (formTrx && formTrx.getAttribute('data-satuan')) || 'kg';
    var uangSimbol = (formTrx && formTrx.getAttribute('data-uang')) || 'Rp';
    var pajakPersen = parseFloat((formTrx && formTrx.getAttribute('data-pajak')) || '0') || 0;

    function rupiah(n) {
      return uangSimbol + Number(n).toLocaleString('id-ID');
    }

    function hargaBaris(row) {
      var sel = row.querySelector('.pilih-paket');
      var berat = row.querySelector('.berat');
      var subtotal = row.querySelector('.subtotal');
      var harga = parseFloat(sel.selectedOptions[0].dataset.harga) || 0;
      var b = parseFloat(berat.value) || 0;
      var sub = Math.round(harga * b);
      if (subtotal) subtotal.textContent = rupiah(sub);
      return { berat: b, sub: sub };
    }

    function hitungTotal() {
      var subtotal = 0, totalBerat = 0, adaBarisValid = false;
      baris.querySelectorAll('.baris-paket').forEach(function (row) {
        var h = hargaBaris(row);
        subtotal += h.sub;
        totalBerat += h.berat;
        if (h.sub > 0) adaBarisValid = true;
      });
      var pajak = Math.round(subtotal * pajakPersen / 100);
      var inpDiskon = document.getElementById('diskon');
      var diskon = inpDiskon ? (parseFloat(inpDiskon.value) || 0) : 0;
      if (diskon > subtotal + pajak) diskon = subtotal + pajak;
      var total = Math.round(subtotal + pajak - diskon);
      var v = document.getElementById('totalHargaView');
      var vb = document.getElementById('totalBerat');
      var vp = document.getElementById('pajakView');
      var vd = document.getElementById('diskonView');
      var simpan = document.getElementById('tombolSimpan');
      if (v) v.textContent = rupiah(total);
      if (vb) vb.textContent = Number(totalBerat).toLocaleString('id-ID') + ' ' + satuan;
      if (vp) vp.textContent = rupiah(pajak);
      if (vd) vd.textContent = diskon > 0 ? '- ' + rupiah(diskon) : '- ' + rupiah(0);
      if (simpan) simpan.disabled = !adaBarisValid;
    }

    var inpDiskon = document.getElementById('diskon');
    if (inpDiskon) inpDiskon.addEventListener('input', hitungTotal);

    function tambahBaris() {
      var row = tmpl.content.firstElementChild.cloneNode(true);
      baris.appendChild(row);
      row.querySelector('.pilih-paket').focus();
      hitungTotal();
    }

    // Delegasi event
    baris.addEventListener('input', hitungTotal);
    baris.addEventListener('change', hitungTotal);
    baris.addEventListener('click', function (ev) {
      var btn = ev.target.closest('.hapus-baris');
      if (btn) {
        btn.closest('.baris-paket').remove();
        hitungTotal();
      }
    });

    if (baris.querySelectorAll('.baris-paket').length === 0) tambahBaris();
    btnTambah.addEventListener('click', tambahBaris);
    hitungTotal();
  }

  /* ================= Jam hidup di topbar ================= */
  var jamHidup = document.getElementById('jamHeader');
  if (jamHidup) {
    function setJam() {
      var d = new Date();
      var p = function (n) { return (n < 10 ? '0' : '') + n; };
      jamHidup.textContent = p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
    }
    setJam();
    setInterval(setJam, 1000);
  }

  /* ================= Tombol kembali ke atas ================= */
  var btnTop = document.getElementById('btnTop');
  if (btnTop) {
    var syncTop = function () {
      if (window.scrollY > 420) { document.body.classList.add('show-top'); }
      else { document.body.classList.remove('show-top'); }
    };
    window.addEventListener('scroll', syncTop, { passive: true });
    btnTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    syncTop();
  }

  /* ================= Notifikasi (realtime, polling 30 detik) ================= */
  var notifDD = document.getElementById('notifDD');
  var btnNotif = document.getElementById('btnNotif');
  if (notifDD && btnNotif) {
    var notifList = document.getElementById('notifList');
    var notifBadge = document.getElementById('notifBadge');
    var belumSebelumnya = -1;
    var toast = null;
    var audioNotif = null;
    var audioSiap = false;

    /* Suara notifikasi: browser memblokir audio tanpa interaksi pengguna, jadi
       audio disiapkan (diputar senyap lalu di-pause) saat pengguna pertama kali
       menyentuh halaman; setelah itu bunyi diputar kala ada notifikasi baru. */
    function volumeNotif() {
      var v = parseInt(window.LK_NOTIF_VOLUME, 10);
      if (isNaN(v)) v = 80;
      return Math.min(1, Math.max(0, v / 100));
    }
    function siapkanAudio() {
      if (audioSiap || !window.LK_NOTIF_SOUND) return;
      if (window.LK_NOTIF_SUARA === 0) return;
      audioSiap = true;
      audioNotif = new Audio(window.LK_NOTIF_SOUND);
      audioNotif.preload = 'auto';
      audioNotif.volume = 0;
      try {
        var janji = audioNotif.play();
        if (janji && janji.then) {
          janji.then(function () {
            audioNotif.pause();
            audioNotif.currentTime = 0;
            audioNotif.volume = volumeNotif();
          }).catch(function () {});
        }
      } catch (e) {}
    }
    document.addEventListener('pointerdown', siapkanAudio, { once: true });
    document.addEventListener('keydown', siapkanAudio, { once: true });

    function bunyiNotif() {
      if (!audioSiap || !audioNotif) return;
      if (window.LK_NOTIF_SUARA === 0) return;
      try {
        audioNotif.currentTime = 0;
        var janji = audioNotif.play();
        if (janji && janji.catch) janji.catch(function () {});
      } catch (e) {}
    }

    function tutupNotif() { notifDD.classList.remove('open'); btnNotif.setAttribute('aria-expanded', 'false'); }
    function setBadge(n) {
      if (!notifBadge) return;
      if (n > 0) { notifBadge.textContent = n > 99 ? '99+' : n; notifBadge.removeAttribute('hidden'); }
      else { notifBadge.setAttribute('hidden', ''); }
    }

    function renderListItem(n) {
      var a = document.createElement('a');
      a.className = 'notif-item' + (n.dibaca ? '' : ' baru');
      a.href = n.tautan;

      var ikon = document.createElement('span');
      ikon.className = 'notif-ico';
      ikon.innerHTML = n.ikon;

      var txt = document.createElement('span');
      txt.className = 'notif-txt';
      var j = document.createElement('span'); j.className = 'notif-j'; j.textContent = n.judul;
      var p = document.createElement('span'); p.className = 'notif-p'; p.textContent = n.pesan;
      var w = document.createElement('span'); w.className = 'notif-w'; w.textContent = n.waktu;
      txt.appendChild(j); txt.appendChild(p); txt.appendChild(w);

      a.appendChild(ikon); a.appendChild(txt);
      return a;
    }

    function renderList(items) {
      if (!notifList) return;
      notifList.innerHTML = '';
      if (!items || !items.length) {
        notifList.innerHTML = '<span class="notif-empty">Tidak ada notifikasi.</span>';
        return;
      }
      items.forEach(function (n) { notifList.appendChild(renderListItem(n)); });
    }

    /* Polling memakai setTimeout berantai, bukan setInterval, dengan jeda
       memanjang saat terjadi kegagalan. SetInterval + fetch bisa membuat
       permintaan menumpuk ketika server lambat, dan setiap request jadi sia-sia
       ketika tab sedang tidak terlihat. */
    var jedaPolling = 30000;   /* 30 detik saat normal */
    var maksJeda = 300000;     /* 5 menit saat terus gagal */
    var sedangMuat = false;

    function muatNotifikasi() {
      /* Jangan buka permintaan baru sebelum yang sebelumnya selesai. */
      if (sedangMuat) return;
      /* Tab tersembunyi (latar belakang): tidak perlu polling. */
      if (document.hidden) return;

      sedangMuat = true;
      fetch(lkUrl('notifikasi/data'), { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
        .then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(function (j) {
          jedaPolling = 30000; /* kembalikan jeda normal setelah berhasil */
          setBadge(j.belum);
          renderList(j.items);
          if (belumSebelumnya >= 0 && j.belum > belumSebelumnya && j.items && j.items.length) {
            tampilToast(j.items[0].judul);
            bunyiNotif();
          }
          belumSebelumnya = j.belum;
        })
        .catch(function (e) {
          /* Endpoint gagal terus (mis. sesi habis): jeda diperpanjang agar tidak
             membanjiri server, dan alas dicatat di konsol untuk penelusuran. */
          jedaPolling = Math.min(maksJeda, Math.round(jedaPolling * 1.8));
          if (window.console && console.warn) {
            console.warn('[LaundryKu] polling notifikasi gagal:', e && e.message ? e.message : e);
          }
        })
        .then(function () {
          sedangMuat = false;
        });
    }

    function jadwalkanPolling() {
      clearTimeout(timerPolling);
      timerPolling = setTimeout(function () {
        muatNotifikasi();
        jadwalkanPolling();
      }, jedaPolling);
    }

    var timerPolling = null;
    jadwalkanPolling();
    muatNotifikasi();
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) {
        jedaPolling = 30000;
        muatNotifikasi();
      }
    });

    function tampilToast(judul) {
      if (!toast) {
        toast = document.createElement('div');
        toast.className = 'notif-toast';
        toast.addEventListener('click', function () {
          window.location.href = lkUrl('notifikasi');
        });
        document.body.appendChild(toast);
      }
      var isi = document.createElement('span');
      var lbl = document.createElement('span');
      lbl.className = 'nt-judul';
      lbl.textContent = 'Notifikasi baru';
      var pesan = document.createElement('span');
      pesan.className = 'nt-pesan';
      pesan.textContent = judul;   /* judul berasal dari database: pakai textContent, bukan innerHTML */
      isi.appendChild(lbl);
      isi.appendChild(pesan);
      toast.textContent = '';
      toast.appendChild(isi);
      toast.classList.add('show');
      clearTimeout(toast._timer);
      toast._timer = setTimeout(function () { toast.classList.remove('show'); }, 5000);
    }

    btnNotif.addEventListener('click', function (e) {
      e.stopPropagation();
      var buka = !notifDD.classList.contains('open');
      notifDD.classList.toggle('open', buka);
      btnNotif.setAttribute('aria-expanded', buka ? 'true' : 'false');
      if (buka) muatNotifikasi();
    });
    document.addEventListener('click', function (e) {
      if (!notifDD.contains(e.target)) tutupNotif();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') tutupNotif();
    });
  }

  /* ================= Preview suara notifikasi (halaman Pengaturan) ================= */
  var btnCobaSuara = document.getElementById('btnCobaSuara');
  if (btnCobaSuara) {
    btnCobaSuara.addEventListener('click', function () {
      if (!window.LK_NOTIF_SOUND) {
        window.alert('File suara notifikasi belum tersedia di server.');
        return;
      }
      var audio = new Audio(window.LK_NOTIF_SOUND);
      var sel = document.getElementById('notif_volume');
      var v = sel ? parseInt(sel.value, 10) : parseInt(window.LK_NOTIF_VOLUME, 10);
      if (isNaN(v)) v = 80;
      audio.volume = Math.min(1, Math.max(0, v / 100));
      var janji = audio.play();
      if (janji && janji.catch) janji.catch(function () {});
    });
  }
})();