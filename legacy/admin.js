/* ==========================================================================
   BOOK & TEA HOUSE — Admin Panel Logic (V1)
   DataStore (Supabase-Ready), Auth, Dashboard, CRUD & Live QR Preview
   ========================================================================== */

(function () {
  'use strict';

  /* ==========================================================================
     1. DATASTORE (MODULAR & SUPABASE READY)
     ========================================================================== */
  const STORAGE_KEY = 'bnt_admin_data_v1';
  const AUTH_KEY = 'bnt_admin_auth';

  // Initial Seed Data (Aligned with Book & Tea QR Menu)
  const INITIAL_SEED = {
    products: [
      {
        id: "tea_1",
        name: "Earl Grey Royal",
        category: "tea",
        price: 95,
        desc: "Bergamot harmanlı siyah çay, kurutulmuş peygamber çiçeği ve portakal kabuğu ile demlenmiş kraliyet serisi.",
        image: "assets/earl_grey.png",
        active: true,
        bestseller: true,
        isNew: false
      },
      {
        id: "tea_2",
        name: "Japon Sencha Yeşil Çay",
        category: "tea",
        price: 90,
        desc: "Birinci hasat Japon yeşil çay yaprakları; ferahlatıcı ve zengin antioksidan deposu.",
        image: "assets/earl_grey.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "tea_3",
        name: "Papatya & Fransız Lavantası",
        category: "tea",
        price: 85,
        desc: "Dinlendirici organik papatya tomurcukları ve rahatlatıcı Provence lavantası.",
        image: "assets/earl_grey.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "tea_4",
        name: "Chai Tea Latte",
        category: "tea",
        price: 115,
        desc: "Geleneksel Hint baharatları, demlenmiş aromatik siyah çay ve ipeksi süt köpüğü.",
        image: "assets/earl_grey.png",
        active: true,
        bestseller: true,
        isNew: false
      },
      {
        id: "tea_5",
        name: "Buzlu Şeftali & Hibiskus",
        category: "tea",
        price: 105,
        desc: "Ev yapımı organik şeftali püresi, soğuk demlenmiş ekşi hibiskus çayı ve taze nane.",
        image: "assets/earl_grey.png",
        active: true,
        bestseller: false,
        isNew: true
      },
      {
        id: "coff_1",
        name: "Kütüphane Özel Latte",
        category: "coffee",
        price: 120,
        desc: "Çift shot nitelikli Kolombiya espresso, yulaf sütlü kadifemsi doku ve hafif karamel lezzeti.",
        image: "assets/latte.png",
        active: true,
        bestseller: true,
        isNew: false
      },
      {
        id: "coff_2",
        name: "Double Ristretto Espresso",
        category: "coffee",
        price: 75,
        desc: "%100 Arabica nitelikli harman, kısa ve yoğun aroma.",
        image: "assets/latte.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "coff_3",
        name: "Velvet Cappuccino",
        category: "coffee",
        price: 110,
        desc: "Dengeli espresso bazı ve ipeksi kıvamda yoğun mikro süt köpüğü.",
        image: "assets/latte.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "coff_4",
        name: "Cold Brew Reserve",
        category: "coffee",
        price: 125,
        desc: "Etiyopya Yirgacheffe çekirdeklerinden 18 saat soğuk demlenmiş yumuşak içimli nitelikli kahve.",
        image: "assets/latte.png",
        active: true,
        bestseller: true,
        isNew: false
      },
      {
        id: "bakery_1",
        name: "Avokadolu Ekşi Maya Toast",
        category: "bakery",
        price: 165,
        desc: "Kendi fırınımızdan çıkan kızarmış ekşi maya ekmek, sızma zeytinyağlı avokado pürüzü ve poşe yumurta.",
        image: "assets/avocado_toast.png",
        active: true,
        bestseller: true,
        isNew: false
      },
      {
        id: "bakery_2",
        name: "Tereyağlı Fransız Kruvasanı",
        category: "bakery",
        price: 85,
        desc: "Fransız tereyağı ile hazırlanan, kat kat çıtır ve yumuşak taze fırınlanmış kruvasan.",
        image: "assets/avocado_toast.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "dessert_1",
        name: "San Sebastian Cheesecake",
        category: "dessert",
        price: 155,
        desc: "İspanyol usulü fırınlanmış yanık üzeri ve kremsi akışkan içi ile; yanında karadut kompostosu.",
        image: "assets/cheesecake.png",
        active: true,
        bestseller: true,
        isNew: false
      },
      {
        id: "dessert_2",
        name: "Sıcak Çikolatalı Brownie",
        category: "dessert",
        price: 135,
        desc: "%70 Belçika çikolatası ve kıyılmış ceviz içi ile hazırlanan sıcak servis dilim.",
        image: "assets/cheesecake.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "dessert_3",
        name: "Geleneksel Tiramisu",
        category: "dessert",
        price: 160,
        desc: "Mascarpone peynirli hafif krema ve espressolu kedi dili bisküvileri katmanları.",
        image: "assets/cheesecake.png",
        active: true,
        bestseller: false,
        isNew: true
      },
      {
        id: "sand_1",
        name: "Gurme Peynir & Şarküteri Tabağı",
        category: "sandwich",
        price: 195,
        desc: "Üç çeşit olgunlaştırılmış peynir, ceviz içi, kuru incir, kovan balı ve çıtır kıtır ekmekler.",
        image: "assets/avocado_toast.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "sand_2",
        name: "Fesleğenli Mozzarella Panini",
        category: "sandwich",
        price: 160,
        desc: "Sıcak ciabatta ekmeğinde erimiş taze mozzarella, domates ve ev yapımı fesleğen pesto sosu.",
        image: "assets/avocado_toast.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "book_1",
        name: "Book & Tea Seramik Fincan",
        category: "books",
        price: 250,
        desc: "Özel tasarım el yapımı toprak mat seramik fincan. Logolu özel kutusunda.",
        image: "assets/hero.png",
        active: true,
        bestseller: false,
        isNew: false
      },
      {
        id: "book_2",
        name: "Deri Kitap Ayracı & Not Defteri",
        category: "books",
        price: 180,
        desc: "Hakiki deri kitap ayracı ve noktalı kütüphane not defteri seti.",
        image: "assets/hero.png",
        active: true,
        bestseller: false,
        isNew: false
      }
    ],
    categories: [
      { id: "tea", name: "Özel Çaylar", icon: "🍵", order: 1 },
      { id: "coffee", name: "Kahve Sanatı", icon: "☕", order: 2 },
      { id: "bakery", name: "Kütüphane Fırını", icon: "🥐", order: 3 },
      { id: "dessert", name: "Tatlılar", icon: "🍰", order: 4 },
      { id: "sandwich", name: "Sandviç & Tost", icon: "🥪", order: 5 },
      { id: "books", name: "Kitap & Merch", icon: "📖", order: 6 }
    ],
    options: {
      milk: [
        { id: "m_1", name: "Tam Yağlı Süt", price: 0 },
        { id: "m_2", name: "Laktozsuz Süt", price: 0 },
        { id: "m_3", name: "Yulaf Sütü", price: 15 },
        { id: "m_4", name: "Badem Sütü", price: 15 }
      ],
      sugar: [
        { id: "s_1", name: "Şekersiz", price: 0 },
        { id: "s_2", name: "Az Şekerli", price: 0 },
        { id: "s_3", name: "Orta Şekerli", price: 0 },
        { id: "s_4", name: "Çok Şekerli", price: 0 }
      ],
      extras: [
        { id: "e_1", name: "Extra Shot", price: 20 },
        { id: "e_2", name: "Vanilya Şurubu", price: 15 },
        { id: "e_3", name: "Karamel Şurubu", price: 15 },
        { id: "e_4", name: "Krema", price: 15 }
      ]
    },
    // EXACTLY 12 TABLES
    tables: Array.from({ length: 12 }, (_, i) => ({
      id: i + 1,
      name: `Masa ${String(i + 1).padStart(2, '0')}`,
      number: i + 1,
      active: true
    })),
    staff: [
      { id: "st_1", name: "Cemre Yılmaz", role: "Yönetici", status: "Aktif", phone: "+90 532 101 20 30" },
      { id: "st_2", name: "Emre Kaya", role: "Garson", status: "Aktif", phone: "+90 533 202 30 40" },
      { id: "st_3", name: "Selin Demir", role: "Garson", status: "Molada", phone: "+90 535 303 40 50" },
      { id: "st_4", name: "Kaan Arslan", role: "Barista", status: "Aktif", phone: "+90 536 404 50 60" }
    ],
    settings: {
      cafeName: "Book N Tea",
      slogan: "A sanctuary for book lovers & tea connoisseurs",
      phone: "+90 212 555 01 23",
      address: "Kütüphane Cad. No: 42, Moda / Kadıköy",
      instagram: "@booknteahouse",
      hours: "Hergün: 08:30 - 23:30",
      callWaiter: true,
      requestBill: true,
      soundNotification: true
    },
    productOptionMappings: {
      "coff_1": { milk: true, sugar: true, extras: true },
      "coff_3": { milk: true, sugar: true, extras: true },
      "tea_1": { milk: true, sugar: true, extras: false },
      "tea_4": { milk: true, sugar: true, extras: true }
    }
  };

  const DataStore = {
    load() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
          this.saveAll(INITIAL_SEED);
          return INITIAL_SEED;
        }
        const parsed = JSON.parse(raw);
        // Ensure critical arrays/objects exist to prevent undefined.sort() crashes
        if (!Array.isArray(parsed.products)) parsed.products = [];
        if (!Array.isArray(parsed.categories)) parsed.categories = INITIAL_SEED.categories;
        if (!parsed.options || typeof parsed.options !== 'object') parsed.options = INITIAL_SEED.options;
        if (!parsed.options.milk) parsed.options.milk = [];
        if (!parsed.options.sugar) parsed.options.sugar = [];
        if (!parsed.options.extras) parsed.options.extras = [];
        if (!parsed.productOptionMappings || typeof parsed.productOptionMappings !== 'object') {
          parsed.productOptionMappings = INITIAL_SEED.productOptionMappings;
        }
        // Ensure 12 tables format integrity
        if (!parsed.tables || parsed.tables.length !== 12) {
          parsed.tables = INITIAL_SEED.tables;
        }
        return parsed;
      } catch (err) {
        console.error("DataStore load error:", err);
        return INITIAL_SEED;
      }
    },


    saveAll(data) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
      // Canlı sunucuya (Vercel) senkronize et - telefon ve tüm cihazlar anında görsün
      try {
        fetch('/api/menu', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data),
        }).catch(() => {});
      } catch (e) {}
    },

    // --- Products ---
    getProducts() {
      return this.load().products;
    },

    saveProduct(product) {
      const data = this.load();
      if (!product.id) {
        product.id = 'prod_' + Date.now();
        data.products.unshift(product);
      } else {
        const idx = data.products.findIndex(p => p.id === product.id);
        if (idx !== -1) {
          data.products[idx] = { ...data.products[idx], ...product };
        } else {
          data.products.unshift(product);
        }
      }
      this.saveAll(data);
      return product;
    },

    deleteProduct(id) {
      const data = this.load();
      data.products = data.products.filter(p => p.id !== id);
      this.saveAll(data);
    },

    toggleProductStatus(id) {
      const data = this.load();
      const item = data.products.find(p => p.id === id);
      if (item) {
        item.active = !item.active;
        this.saveAll(data);
        return item.active;
      }
      return false;
    },

    // --- Categories ---
    getCategories() {
      const cats = this.load().categories;
      return cats.sort((a, b) => (a.order || 0) - (b.order || 0));
    },

    saveCategory(cat) {
      const data = this.load();
      if (!cat.id) {
        cat.id = 'cat_' + Date.now();
        cat.order = data.categories.length + 1;
        data.categories.push(cat);
      } else {
        const idx = data.categories.findIndex(c => c.id === cat.id);
        if (idx !== -1) {
          data.categories[idx] = { ...data.categories[idx], ...cat };
        }
      }
      this.saveAll(data);
      return cat;
    },

    deleteCategory(id) {
      const data = this.load();
      data.categories = data.categories.filter(c => c.id !== id);
      this.saveAll(data);
    },

    moveCategory(id, direction) {
      const data = this.load();
      const cats = data.categories.sort((a, b) => (a.order || 0) - (b.order || 0));
      const idx = cats.findIndex(c => c.id === id);
      if (idx === -1) return;

      const targetIdx = direction === 'up' ? idx - 1 : idx + 1;
      if (targetIdx < 0 || targetIdx >= cats.length) return;

      const tempOrder = cats[idx].order;
      cats[idx].order = cats[targetIdx].order;
      cats[targetIdx].order = tempOrder;

      this.saveAll(data);
    },

    // --- Options ---
    getOptions() {
      return this.load().options;
    },

    saveOption(group, option) {
      const data = this.load();
      if (!data.options[group]) data.options[group] = [];

      if (!option.id) {
        option.id = group[0] + '_' + Date.now();
        data.options[group].push(option);
      } else {
        const idx = data.options[group].findIndex(o => o.id === option.id);
        if (idx !== -1) {
          data.options[group][idx] = { ...data.options[group][idx], ...option };
        }
      }
      this.saveAll(data);
      return option;
    },

    deleteOption(group, id) {
      const data = this.load();
      if (data.options[group]) {
        data.options[group] = data.options[group].filter(o => o.id !== id);
        this.saveAll(data);
      }
    },

    // Product Mapping
    getProductOptionMapping(productId) {
      const data = this.load();
      return (data.productOptionMappings && data.productOptionMappings[productId]) || { milk: false, sugar: false, extras: false };
    },

    saveProductOptionMapping(productId, mapping) {
      const data = this.load();
      if (!data.productOptionMappings) data.productOptionMappings = {};
      data.productOptionMappings[productId] = mapping;
      this.saveAll(data);
    },

    // --- Tables (Exactly 12) ---
    getTables() {
      return this.load().tables;
    },

    toggleTableStatus(id) {
      const data = this.load();
      const t = data.tables.find(tbl => tbl.id === Number(id));
      if (t) {
        t.active = !t.active;
        this.saveAll(data);
        return t.active;
      }
      return false;
    },

    // --- Staff ---
    getStaff() {
      return this.load().staff;
    },

    saveStaff(member) {
      const data = this.load();
      if (!member.id) {
        member.id = 'st_' + Date.now();
        data.staff.unshift(member);
      } else {
        const idx = data.staff.findIndex(s => s.id === member.id);
        if (idx !== -1) {
          data.staff[idx] = { ...data.staff[idx], ...member };
        }
      }
      this.saveAll(data);
      return member;
    },

    deleteStaff(id) {
      const data = this.load();
      data.staff = data.staff.filter(s => s.id !== id);
      this.saveAll(data);
    },

    // --- Settings ---
    getSettings() {
      return this.load().settings;
    },

    saveSettings(settings) {
      const data = this.load();
      data.settings = { ...data.settings, ...settings };
      this.saveAll(data);
      return data.settings;
    }
  };

  // Expose DataStore globally for debugging / future Supabase connector
  window.DataStore = DataStore;

  /* ==========================================================================
     2. AUTHENTICATION CONTROLLER (admin / şifre123)
     ========================================================================== */
  const loginScreen = document.getElementById('loginScreen');
  const loginForm = document.getElementById('loginForm');
  const loginUser = document.getElementById('loginUser');
  const loginPass = document.getElementById('loginPass');
  const loginError = document.getElementById('loginError');
  const logoutBtn = document.getElementById('logoutBtn');

  function checkAuth() {
    const isAuth = sessionStorage.getItem(AUTH_KEY);
    if (isAuth === 'true') {
      loginScreen.classList.add('is-hidden');
    } else {
      loginScreen.classList.remove('is-hidden');
      if (loginUser) loginUser.focus();
    }
  }

  if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const user = (loginUser.value || '').trim();
      const pass = (loginPass.value || '').trim();

      if (user === 'admin' && (pass === 'şifre123' || pass === 'sifre123')) {
        sessionStorage.setItem(AUTH_KEY, 'true');
        loginError.classList.remove('is-visible');
        loginScreen.classList.add('is-hidden');
        showToast('Giriş başarılı! Book & Tea Yönetim Paneline hoş geldiniz.', 'success');
        refreshAllViews();
      } else {
        loginError.classList.add('is-visible');
        loginPass.value = '';
        loginPass.focus();
      }
    });
  }

  if (logoutBtn) {
    logoutBtn.addEventListener('click', function () {
      sessionStorage.removeItem(AUTH_KEY);
      loginScreen.classList.remove('is-hidden');
      loginUser.value = '';
      loginPass.value = '';
      showToast('Güvenli çıkış yapıldı.', 'info');
    });
  }

  /* ==========================================================================
     3. NAVIGATION & VIEW ROUTER
     ========================================================================== */
  const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
  const sections = document.querySelectorAll('.admin-section');
  const topbarTitle = document.getElementById('topbarTitle');
  const topbarBreadcrumb = document.getElementById('topbarBreadcrumb');
  const mobileNavToggle = document.getElementById('mobileNavToggle');
  const sidebar = document.getElementById('sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const topbarQuickAddBtn = document.getElementById('topbarQuickAddBtn');

  const VIEW_TITLES = {
    dashboard: { title: 'Dashboard', desc: 'Genel Bakış & Canlı İstatistikler' },
    menu: { title: 'Menü Yönetimi', desc: 'QR Menüdeki Tüm Lezzetler' },
    categories: { title: 'Kategori Yönetimi', desc: 'Menü Grupları ve Sıralama' },
    options: { title: 'Ürün Seçenekleri', desc: 'Süt, Şeker ve Ekstra Tercihleri' },
    tables: { title: 'Masa Yönetimi', desc: '12 Masa ve Canlı QR Kodları' },
    staff: { title: 'Personel Yönetimi', desc: 'Kafe ve Mutfak Ekibi' },
    settings: { title: 'Ayarlar', desc: 'Kafe Bilgileri ve Servis Seçenekleri' }
  };

  window.navigateTo = function (viewId) {
    // Nav links active state
    navLinks.forEach(link => {
      if (link.dataset.nav === viewId) {
        link.classList.add('is-active');
      } else {
        link.classList.remove('is-active');
      }
    });

    // Sections active state
    sections.forEach(sec => {
      if (sec.id === 'section-' + viewId) {
        sec.classList.add('is-active');
      } else {
        sec.classList.remove('is-active');
      }
    });

    // Topbar titles
    if (VIEW_TITLES[viewId]) {
      topbarTitle.textContent = VIEW_TITLES[viewId].title;
      topbarBreadcrumb.textContent = VIEW_TITLES[viewId].desc;
    }

    // Close mobile sidebar if open
    closeMobileSidebar();

    // Rerender view if necessary
    if (viewId === 'dashboard') renderDashboard();
    if (viewId === 'menu') renderMenu();
    if (viewId === 'categories') renderCategories();
    if (viewId === 'options') renderOptions();
    if (viewId === 'tables') renderTables();
    if (viewId === 'staff') renderStaff();
    if (viewId === 'settings') renderSettings();
  };

  navLinks.forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const targetView = this.dataset.nav;
      navigateTo(targetView);
    });
  });

  function openMobileSidebar() {
    sidebar.classList.add('is-mobile-open');
    sidebarBackdrop.classList.add('is-mobile-open');
  }

  function closeMobileSidebar() {
    sidebar.classList.remove('is-mobile-open');
    sidebarBackdrop.classList.remove('is-mobile-open');
  }

  const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
  if (mobileNavToggle) mobileNavToggle.addEventListener('click', openMobileSidebar);
  if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeMobileSidebar);
  if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeMobileSidebar);

  if (topbarQuickAddBtn) {
    topbarQuickAddBtn.addEventListener('click', function () {
      openProductModal();
    });
  }

  /* ==========================================================================
     4. DASHBOARD RENDERER & STATS
     ========================================================================== */
  function renderDashboard() {
    const products = DataStore.getProducts();
    const categories = DataStore.getCategories();
    const tables = DataStore.getTables();

    // Stats
    const activeProducts = products.filter(p => p.active);
    const passiveProducts = products.filter(p => !p.active);

    document.getElementById('statTotalProducts').textContent = products.length;
    document.getElementById('statActiveProducts').textContent = activeProducts.length;
    document.getElementById('statPassiveProducts').textContent = passiveProducts.length;
    document.getElementById('statTotalCategories').textContent = categories.length;
    document.getElementById('statTotalTables').textContent = tables.length;

    // Recent Products List (Top 4)
    const recentContainer = document.getElementById('dashboardRecentList');
    const recent = [...products].slice(0, 4);
    recentContainer.innerHTML = recent.map(p => `
      <div class="mini-product-item">
        <div class="mini-product-info">
          <img class="mini-product-thumb" src="${escapeHtml(p.image || 'assets/latte.png')}" alt="${escapeHtml(p.name)}" />
          <div>
            <div class="mini-product-name">${escapeHtml(p.name)}</div>
            <div class="mini-product-cat">${getCategoryName(p.category)}</div>
          </div>
        </div>
        <div class="mini-product-price">${p.price} ₺</div>
      </div>
    `).join('');

    // Popular Products List (Top 4 bestsellers)
    const popularContainer = document.getElementById('dashboardPopularList');
    const popular = products.filter(p => p.bestseller).slice(0, 4);
    popularContainer.innerHTML = (popular.length ? popular : products.slice(0, 4)).map(p => `
      <div class="mini-product-item">
        <div class="mini-product-info">
          <img class="mini-product-thumb" src="${escapeHtml(p.image || 'assets/latte.png')}" alt="${escapeHtml(p.name)}" />
          <div>
            <div class="mini-product-name">${escapeHtml(p.name)}</div>
            <div class="mini-product-cat">${getCategoryName(p.category)}</div>
          </div>
        </div>
        <div style="text-align: right;">
          <div class="mini-product-price">${p.price} ₺</div>
          <span class="badge-pill badge-pill--bestseller" style="font-size: 0.68rem; padding: 0.1rem 0.4rem;">Popüler</span>
        </div>
      </div>
    `).join('');

    // Tables quick status (12 tables)
    const tablesSummary = document.getElementById('dashboardTablesSummary');
    tablesSummary.innerHTML = tables.map(t => `
      <div class="dashboard-table-dot ${t.active ? 'is-active' : 'is-passive'}">
        <span>${t.name}</span>
        <span style="font-size: 0.68rem; opacity: 0.85;">${t.active ? 'Aktif' : 'Pasif'}</span>
      </div>
    `).join('');

    // Render Phone Mockup
    renderPhoneMockup();
  }

  /* ==========================================================================
     5. PHONE MOCKUP (LIVE QR MENU PREVIEW)
     ========================================================================== */
  function renderPhoneMockup() {
    const categories = DataStore.getCategories();
    const products = DataStore.getProducts().filter(p => p.active);

    const phoneCats = document.getElementById('phoneCategories');
    const phoneList = document.getElementById('phoneProductList');

    if (!phoneCats || !phoneList) return;

    // Categories in Phone
    phoneCats.innerHTML = `
      <div class="phone-cat-chip is-active">Tümü</div>
      ${categories.map(c => `<div class="phone-cat-chip">${escapeHtml(c.icon)} ${escapeHtml(c.name)}</div>`).join('')}
    `;

    // Products inside Phone Screen
    phoneList.innerHTML = products.slice(0, 8).map(p => `
      <div class="phone-product-card">
        <img class="phone-product-img" src="${escapeHtml(p.image || 'assets/latte.png')}" alt="" />
        <div class="phone-product-details">
          <div class="phone-product-name">${escapeHtml(p.name)}</div>
          <div class="phone-product-desc">${escapeHtml(p.desc || '')}</div>
          <div class="phone-product-foot">
            <span class="phone-product-price">${p.price} ₺</span>
            <span class="phone-add-btn">+</span>
          </div>
        </div>
      </div>
    `).join('');
  }

  /* ==========================================================================
     6. MENU MANAGEMENT (CRUD & FILTERS)
     ========================================================================== */
  const menuSearchInput = document.getElementById('menuSearchInput');
  const menuSearchClear = document.getElementById('menuSearchClear');
  const menuCategoryFilter = document.getElementById('menuCategoryFilter');
  const menuStatusFilter = document.getElementById('menuStatusFilter');
  const productsTableBody = document.getElementById('productsTableBody');
  const btnOpenAddProduct = document.getElementById('btnOpenAddProduct');

  function renderMenu() {
    const products = DataStore.getProducts();
    const categories = DataStore.getCategories();

    // Populate Category filter dropdown
    if (menuCategoryFilter) {
      const currentCatVal = menuCategoryFilter.value;
      menuCategoryFilter.innerHTML = '<option value="all">Tüm Kategoriler</option>' +
        categories.map(c => `<option value="${c.id}">${escapeHtml(c.icon)} ${escapeHtml(c.name)}</option>`).join('');
      menuCategoryFilter.value = currentCatVal || 'all';
    }

    // Apply Filters
    const query = (menuSearchInput ? menuSearchInput.value : '').trim().toLowerCase();
    const selectedCat = menuCategoryFilter ? menuCategoryFilter.value : 'all';
    const selectedStatus = menuStatusFilter ? menuStatusFilter.value : 'all';

    const filtered = products.filter(p => {
      const matchesQuery = !query ||
        (p.name && p.name.toLowerCase().includes(query)) ||
        (p.desc && p.desc.toLowerCase().includes(query));

      const matchesCat = selectedCat === 'all' || p.category === selectedCat;

      const matchesStatus =
        selectedStatus === 'all' ||
        (selectedStatus === 'active' && p.active) ||
        (selectedStatus === 'passive' && !p.active);

      return matchesQuery && matchesCat && matchesStatus;
    });

    if (!productsTableBody) return;

    if (filtered.length === 0) {
      productsTableBody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--coffee-muted);">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">☕</div>
            <div style="font-weight: 600;">Eşleşen ürün bulunamadı</div>
            <div style="font-size: 0.85rem;">Filtrelerinizi değiştirmeyi veya yeni ürün eklemeyi deneyebilirsiniz.</div>
          </td>
        </tr>
      `;
      return;
    }

    productsTableBody.innerHTML = filtered.map(p => `
      <tr class="table-row-hover">
        <td style="width: 70px;">
          <img class="product-cell-img" src="${escapeHtml(p.image || 'assets/latte.png')}" alt="${escapeHtml(p.name)}" />
        </td>
        <td>
          <div class="product-cell-info">
            <span class="product-cell-title">${escapeHtml(p.name)}</span>
            <span class="product-cell-desc">${escapeHtml(p.desc || '-')}</span>
          </div>
        </td>
        <td>
          <span class="badge-pill" style="background: var(--bg-cream-base); color: var(--coffee-dark);">
            ${getCategoryName(p.category)}
          </span>
        </td>
        <td>
          <span class="product-price-cell">${p.price} ₺</span>
        </td>
        <td>
          <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
            ${p.bestseller ? '<span class="badge-pill badge-pill--bestseller">⭐ Popüler</span>' : ''}
            ${p.isNew ? '<span class="badge-pill badge-pill--new">✨ Yeni</span>' : ''}
            ${!p.bestseller && !p.isNew ? '<span style="color: var(--coffee-light); font-size: 0.78rem;">-</span>' : ''}
          </div>
        </td>
        <td>
          <label class="switch-control" title="Ürünü Aktif/Pasif Yap">
            <input type="checkbox" ${p.active ? 'checked' : ''} onchange="toggleProduct('${p.id}')" />
            <span class="switch-slider"></span>
          </label>
        </td>
        <td>
          <div class="table-actions">
            <button class="btn-icon-square" type="button" title="Düzenle" onclick="editProduct('${p.id}')">
              ✎
            </button>
            <button class="btn-icon-square" style="color: var(--color-danger);" type="button" title="Sil" onclick="deleteProductPrompt('${p.id}', '${escapeHtml(p.name)}')">
              🗑️
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  window.toggleProduct = function (id) {
    const newState = DataStore.toggleProductStatus(id);
    showToast(`Ürün durumu ${newState ? 'Aktif' : 'Pasif'} olarak güncellendi.`, 'success');
    renderMenu();
    renderDashboard();
  };

  if (menuSearchInput) {
    menuSearchInput.addEventListener('input', function () {
      if (menuSearchClear) {
        if (this.value) menuSearchClear.classList.add('is-active');
        else menuSearchClear.classList.remove('is-active');
      }
      renderMenu();
    });
  }

  if (menuSearchClear) {
    menuSearchClear.addEventListener('click', function () {
      menuSearchInput.value = '';
      menuSearchClear.classList.remove('is-active');
      renderMenu();
    });
  }

  if (menuCategoryFilter) menuCategoryFilter.addEventListener('change', renderMenu);
  if (menuStatusFilter) menuStatusFilter.addEventListener('change', renderMenu);

  // Product Add / Edit Modal
  const productModal = document.getElementById('productModal');
  const productForm = document.getElementById('productForm');
  const productModalTitle = document.getElementById('productModalTitle');
  const productFormId = document.getElementById('productFormId');
  const productFormName = document.getElementById('productFormName');
  const productFormDesc = document.getElementById('productFormDesc');
  const productFormCategory = document.getElementById('productFormCategory');
  const productFormPrice = document.getElementById('productFormPrice');
  const productFormImg = document.getElementById('productFormImg');
  const productFormActive = document.getElementById('productFormActive');
  const productFormBestseller = document.getElementById('productFormBestseller');
  const productFormNew = document.getElementById('productFormNew');
  const imagePresets = document.querySelectorAll('.image-preset-option');

  function openProductModal(prod = null) {
    const categories = DataStore.getCategories();
    productFormCategory.innerHTML = categories.map(c => `
      <option value="${c.id}">${escapeHtml(c.icon)} ${escapeHtml(c.name)}</option>
    `).join('');

    if (prod) {
      productModalTitle.textContent = 'Ürünü Düzenle';
      productFormId.value = prod.id;
      productFormName.value = prod.name || '';
      productFormDesc.value = prod.desc || '';
      productFormCategory.value = prod.category || (categories[0] ? categories[0].id : '');
      productFormPrice.value = prod.price || 0;
      productFormImg.value = prod.image || 'assets/latte.png';
      productFormActive.checked = prod.active !== false;
      productFormBestseller.checked = !!prod.bestseller;
      productFormNew.checked = !!prod.isNew;
    } else {
      productModalTitle.textContent = 'Yeni Ürün Ekle';
      productFormId.value = '';
      productForm.reset();
      productFormImg.value = 'assets/latte.png';
      productFormActive.checked = true;
    }

    // Select matching preset if any
    updatePresetSelection(productFormImg.value);
    openModal('productModal');
  }

  window.editProduct = function (id) {
    const prod = DataStore.getProducts().find(p => p.id === id);
    if (prod) openProductModal(prod);
  };

  window.deleteProductPrompt = function (id, name) {
    openConfirmModal(
      `"${name}" ürününü silmek istediğinize emin misiniz?`,
      'Bu ürün menüden tamamen kaldırılacaktır.',
      function () {
        DataStore.deleteProduct(id);
        showToast(`"${name}" başarıyla silindi.`, 'success');
        renderMenu();
        renderDashboard();
      }
    );
  };

  imagePresets.forEach(preset => {
    preset.addEventListener('click', function () {
      const imgPath = this.dataset.img;
      productFormImg.value = imgPath;
      updatePresetSelection(imgPath);
    });
  });

  function updatePresetSelection(path) {
    imagePresets.forEach(p => {
      if (p.dataset.img === path) p.classList.add('is-selected');
      else p.classList.remove('is-selected');
    });
  }

  if (productFormImg) {
    productFormImg.addEventListener('input', function () {
      updatePresetSelection(this.value.trim());
    });
  }

  if (btnOpenAddProduct) {
    btnOpenAddProduct.addEventListener('click', function () {
      openProductModal();
    });
  }

  if (productForm) {
    productForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const id = productFormId.value;
      const name = productFormName.value.trim();
      const desc = productFormDesc.value.trim();
      const category = productFormCategory.value;
      const price = Number(productFormPrice.value) || 0;
      const image = productFormImg.value.trim() || 'assets/latte.png';
      const active = productFormActive.checked;
      const bestseller = productFormBestseller.checked;
      const isNew = productFormNew.checked;

      if (!name) {
        showToast('Lütfen ürün adını giriniz.', 'danger');
        return;
      }

      DataStore.saveProduct({
        id: id || undefined,
        name,
        desc,
        category,
        price,
        image,
        active,
        bestseller,
        isNew
      });

      closeModal('productModal');
      showToast(id ? 'Ürün başarıyla güncellendi.' : 'Yeni ürün menüye eklendi!', 'success');
      renderMenu();
      renderDashboard();
    });
  }

  /* ==========================================================================
     7. CATEGORIES MANAGEMENT
     ========================================================================== */
  const categoriesGrid = document.getElementById('categoriesGrid');
  const btnOpenAddCategory = document.getElementById('btnOpenAddCategory');
  const categoryModal = document.getElementById('categoryModal');
  const categoryForm = document.getElementById('categoryForm');
  const categoryFormId = document.getElementById('categoryFormId');
  const categoryFormName = document.getElementById('categoryFormName');
  const categoryFormIcon = document.getElementById('categoryFormIcon');
  const categoryModalTitle = document.getElementById('categoryModalTitle');

  function renderCategories() {
    const categories = DataStore.getCategories();
    const products = DataStore.getProducts();

    if (!categoriesGrid) return;

    categoriesGrid.innerHTML = categories.map((cat, idx) => {
      const count = products.filter(p => p.category === cat.id).length;
      return `
        <div class="category-card">
          <div class="category-card-left">
            <div class="category-icon">${escapeHtml(cat.icon)}</div>
            <div>
              <div class="category-name">${escapeHtml(cat.name)}</div>
              <div class="category-count">${count} Çeşit Ürün</div>
            </div>
          </div>
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <div class="category-order-controls">
              <button class="btn-icon-square" style="width:30px; height:30px;" type="button" title="Yukarı Taşı" onclick="moveCategoryOrder('${cat.id}', 'up')" ${idx === 0 ? 'disabled style="opacity:0.4;"' : ''}>▲</button>
              <button class="btn-icon-square" style="width:30px; height:30px;" type="button" title="Aşağı Taşı" onclick="moveCategoryOrder('${cat.id}', 'down')" ${idx === categories.length - 1 ? 'disabled style="opacity:0.4;"' : ''}>▼</button>
            </div>
            <button class="btn-icon-square" type="button" title="Düzenle" onclick="editCategory('${cat.id}')">✎</button>
            <button class="btn-icon-square" style="color: var(--color-danger);" type="button" title="Sil" onclick="deleteCategoryPrompt('${cat.id}', '${escapeHtml(cat.name)}')">🗑️</button>
          </div>
        </div>
      `;
    }).join('');
  }

  const btnSaveCategory = document.getElementById('btnSaveCategory');

  window.moveCategoryOrder = function (id, direction) {
    DataStore.moveCategory(id, direction);
    renderCategories();
    renderDashboard();
    renderMenu();
    renderPhoneMockup();
  };

  window.editCategory = function (id) {
    const cat = DataStore.getCategories().find(c => c.id === id);
    if (cat) {
      categoryModalTitle.textContent = 'Kategoriyi Düzenle';
      categoryFormId.value = cat.id;
      categoryFormName.value = cat.name;
      categoryFormIcon.value = cat.icon;
      openModal('categoryModal');
    }
  };

  window.deleteCategoryPrompt = function (id, name) {
    openConfirmModal(
      `"${name}" kategorisini silmek istiyor musunuz?`,
      'Bu kategorideki ürünler kategorisiz kalabilir.',
      function () {
        DataStore.deleteCategory(id);
        showToast(`"${name}" kategorisi silindi.`, 'success');
        renderCategories();
        renderDashboard();
        renderMenu();
        renderPhoneMockup();
      }
    );
  };

  if (btnOpenAddCategory) {
    btnOpenAddCategory.addEventListener('click', function () {
      categoryModalTitle.textContent = 'Yeni Kategori Ekle';
      categoryFormId.value = '';
      categoryForm.reset();
      openModal('categoryModal');
    });
  }

  function handleSaveCategory() {
    const id = categoryFormId.value;
    const name = categoryFormName.value.trim();
    const icon = categoryFormIcon.value.trim() || '☕';

    if (!name) {
      showToast('Kategori adı gerekli.', 'danger');
      return;
    }

    DataStore.saveCategory({
      id: id || undefined,
      name,
      icon
    });

    closeModal('categoryModal');
    showToast(id ? 'Kategori güncellendi.' : 'Yeni kategori oluşturuldu.', 'success');
    renderCategories();
    renderDashboard();
    renderMenu();
    renderPhoneMockup();
  }

  if (categoryForm) {
    categoryForm.addEventListener('submit', function (e) {
      e.preventDefault();
      handleSaveCategory();
    });
  }

  if (btnSaveCategory) {
    btnSaveCategory.addEventListener('click', function (e) {
      handleSaveCategory();
    });
  }

  /* ==========================================================================
     8. PRODUCT OPTIONS (MILK, SUGAR, EXTRAS & MAPPING)
     ========================================================================== */
  const milkOptionsList = document.getElementById('milkOptionsList');
  const sugarOptionsList = document.getElementById('sugarOptionsList');
  const extrasOptionsList = document.getElementById('extrasOptionsList');
  const milkCountBadge = document.getElementById('milkCountBadge');
  const sugarCountBadge = document.getElementById('sugarCountBadge');
  const extrasCountBadge = document.getElementById('extrasCountBadge');
  const mappingProductSelect = document.getElementById('mappingProductSelect');
  const mapMilkCheck = document.getElementById('mapMilkCheck');
  const mapSugarCheck = document.getElementById('mapSugarCheck');
  const mapExtrasCheck = document.getElementById('mapExtrasCheck');
  const btnSaveMapping = document.getElementById('btnSaveMapping');
  const btnOpenAddOption = document.getElementById('btnOpenAddOption');

  const optionModal = document.getElementById('optionModal');
  const optionForm = document.getElementById('optionForm');
  const optionFormGroup = document.getElementById('optionFormGroup');
  const optionFormName = document.getElementById('optionFormName');
  const optionFormPrice = document.getElementById('optionFormPrice');

  function renderOptions() {
    const opts = DataStore.getOptions();

    // Render Milk
    if (milkOptionsList) {
      milkOptionsList.innerHTML = opts.milk.map(m => `
        <div class="option-pill-item">
          <span>${escapeHtml(m.name)}</span>
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <span class="option-pill-price">${m.price > 0 ? '+' + m.price + ' ₺' : 'Ücretsiz'}</span>
            <button class="btn-danger-ghost" style="padding:2px 6px;" type="button" onclick="deleteOptionItem('milk', '${m.id}')">✕</button>
          </div>
        </div>
      `).join('');
      if (milkCountBadge) milkCountBadge.textContent = opts.milk.length + ' Seçenek';
    }

    // Render Sugar
    if (sugarOptionsList) {
      sugarOptionsList.innerHTML = opts.sugar.map(s => `
        <div class="option-pill-item">
          <span>${escapeHtml(s.name)}</span>
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <span class="option-pill-price">Ücretsiz</span>
            <button class="btn-danger-ghost" style="padding:2px 6px;" type="button" onclick="deleteOptionItem('sugar', '${s.id}')">✕</button>
          </div>
        </div>
      `).join('');
      if (sugarCountBadge) sugarCountBadge.textContent = opts.sugar.length + ' Seçenek';
    }

    // Render Extras
    if (extrasOptionsList) {
      extrasOptionsList.innerHTML = opts.extras.map(e => `
        <div class="option-pill-item">
          <span>${escapeHtml(e.name)}</span>
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <span class="option-pill-price">+${e.price} ₺</span>
            <button class="btn-danger-ghost" style="padding:2px 6px;" type="button" onclick="deleteOptionItem('extras', '${e.id}')">✕</button>
          </div>
        </div>
      `).join('');
      if (extrasCountBadge) extrasCountBadge.textContent = opts.extras.length + ' Seçenek';
    }

    // Populate Mapping Product Select
    const products = DataStore.getProducts();
    if (mappingProductSelect) {
      const currentSelected = mappingProductSelect.value || (products[0] ? products[0].id : '');
      mappingProductSelect.innerHTML = products.map(p => `
        <option value="${p.id}">${escapeHtml(p.name)} (${getCategoryName(p.category)})</option>
      `).join('');
      mappingProductSelect.value = currentSelected;
      updateMappingCheckboxes();
    }
  }

  window.deleteOptionItem = function (group, id) {
    DataStore.deleteOption(group, id);
    showToast('Seçenek silindi.', 'info');
    renderOptions();
  };

  function updateMappingCheckboxes() {
    if (!mappingProductSelect) return;
    const prodId = mappingProductSelect.value;
    const mapping = DataStore.getProductOptionMapping(prodId);
    if (mapMilkCheck) mapMilkCheck.checked = !!mapping.milk;
    if (mapSugarCheck) mapSugarCheck.checked = !!mapping.sugar;
    if (mapExtrasCheck) mapExtrasCheck.checked = !!mapping.extras;
  }

  if (mappingProductSelect) {
    mappingProductSelect.addEventListener('change', updateMappingCheckboxes);
  }

  if (btnSaveMapping) {
    btnSaveMapping.addEventListener('click', function () {
      const prodId = mappingProductSelect.value;
      if (!prodId) return;

      const milkChecked = mapMilkCheck.checked;
      const sugarChecked = mapSugarCheck.checked;
      const extrasChecked = mapExtrasCheck.checked;

      DataStore.saveProductOptionMapping(prodId, {
        milk: milkChecked,
        sugar: sugarChecked,
        extras: extrasChecked
      });

      // Ürünün customizable bayrağını güncelle (herhangi bir seçenek aktifse true)
      const isCustomizable = milkChecked || sugarChecked || extrasChecked;
      const data = DataStore.load();
      const product = data.products.find(p => p.id === prodId);
      if (product) {
        product.customizable = isCustomizable;
        DataStore.saveAll(data);
      }

      showToast('Ürün seçenek eşleştirmesi kaydedildi.', 'success');
    });
  }

  if (btnOpenAddOption) {
    btnOpenAddOption.addEventListener('click', function () {
      optionForm.reset();
      openModal('optionModal');
    });
  }

  if (optionForm) {
    optionForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const group = optionFormGroup.value;
      const name = optionFormName.value.trim();
      const price = Number(optionFormPrice.value) || 0;

      if (!name) {
        showToast('Seçenek adı zorunludur.', 'danger');
        return;
      }

      DataStore.saveOption(group, { name, price });
      closeModal('optionModal');
      showToast('Seçenek başarıyla eklendi.', 'success');
      renderOptions();
    });
  }

  /* ==========================================================================
     9. TABLES MANAGEMENT (EXACTLY 12 TABLES & QR INTEGRATION)
     ========================================================================== */
  const tablesGrid = document.getElementById('tablesGrid');

  function renderTables() {
    const tables = DataStore.getTables();
    if (!tablesGrid) return;

    tablesGrid.innerHTML = tables.map(t => {
      const targetUrl = `${window.location.origin}/index.html?masa=${t.number}`;
      const qrSvg = generateQrSvg(targetUrl);

      return `
        <div class="table-card ${t.active ? '' : 'is-passive'}" id="table-card-${t.id}">
          <div class="table-card-top">
            <span class="table-badge-large">📍 ${t.name}</span>
            <label class="switch-control" title="Masa Sipariş Durumu">
              <input type="checkbox" ${t.active ? 'checked' : ''} onchange="toggleTable('${t.id}')" />
              <span class="switch-slider"></span>
            </label>
          </div>

          <div class="table-qr-box" onclick="openTableQrModal('${t.number}', '${t.name}', '${escapeHtml(targetUrl)}')">
            ${qrSvg}
          </div>

          <div class="table-url-text">/?masa=${t.number}</div>

          <div class="table-actions-row">
            <button class="btn-secondary" type="button" onclick="openTableQrModal('${t.number}', '${t.name}', '${escapeHtml(targetUrl)}')">
              🔍 QR Büyüt
            </button>
            <a class="btn-secondary" href="index.html?masa=${t.number}" target="_blank">
              📱 Menü Aç ↗
            </a>
          </div>
        </div>
      `;
    }).join('');
  }

  window.toggleTable = function (id) {
    const newState = DataStore.toggleTableStatus(id);
    showToast(`Masa ${id} ${newState ? 'Aktif' : 'Pasif'} yapıldı.`, 'success');
    renderTables();
    renderDashboard();
  };

  function generateQrSvg(url) {
    try {
      if (typeof qrcode !== 'undefined') {
        const qr = qrcode(0, 'M');
        qr.addData(url);
        qr.make();
        return qr.createSvgTag(4, 2);
      }
    } catch (e) {
      console.warn("QR generator warning:", e);
    }
    return '<div style="font-size: 0.8rem; color: #888;">QR Kod Hazır</div>';
  }

  // Table QR Modal
  const tableQrModal = document.getElementById('tableQrModal');
  const tableQrModalTitle = document.getElementById('tableQrModalTitle');
  const tableQrModalCode = document.getElementById('tableQrModalCode');
  const tableQrModalUrl = document.getElementById('tableQrModalUrl');
  const tableQrOpenLink = document.getElementById('tableQrOpenLink');
  const tableQrPrintBtn = document.getElementById('tableQrPrintBtn');

  window.openTableQrModal = function (tableNum, tableName, targetUrl) {
    tableQrModalTitle.textContent = tableName + ' QR Kodu';
    tableQrModalCode.innerHTML = generateQrSvg(targetUrl);
    tableQrModalUrl.textContent = targetUrl;
    tableQrOpenLink.href = 'index.html?masa=' + tableNum;

    if (tableQrPrintBtn) {
      tableQrPrintBtn.onclick = function () {
        window.print();
      };
    }

    openModal('tableQrModal');
  };

  /* ==========================================================================
     10. STAFF MANAGEMENT
     ========================================================================== */
  const staffGrid = document.getElementById('staffGrid');
  const btnOpenAddStaff = document.getElementById('btnOpenAddStaff');
  const staffModal = document.getElementById('staffModal');
  const staffForm = document.getElementById('staffForm');
  const staffFormId = document.getElementById('staffFormId');
  const staffFormName = document.getElementById('staffFormName');
  const staffFormRole = document.getElementById('staffFormRole');
  const staffFormStatus = document.getElementById('staffFormStatus');
  const staffFormPhone = document.getElementById('staffFormPhone');
  const staffModalTitle = document.getElementById('staffModalTitle');

  function renderStaff() {
    const staff = DataStore.getStaff();
    if (!staffGrid) return;

    staffGrid.innerHTML = staff.map(s => {
      const initial = (s.name || 'P').charAt(0).toUpperCase();
      return `
        <div class="staff-card">
          <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div class="staff-avatar-circle">${initial}</div>
            <div>
              <div class="staff-name">${escapeHtml(s.name)}</div>
              <div style="display: flex; gap: 0.4rem; align-items: center; margin-top: 0.25rem;">
                <span class="staff-role-pill">${escapeHtml(s.role)}</span>
                <span class="badge-pill ${s.status === 'Aktif' ? 'badge-pill--active' : 'badge-pill--passive'}">${escapeHtml(s.status)}</span>
              </div>
              ${s.phone ? `<div style="font-size: 0.75rem; color: var(--coffee-muted); margin-top: 0.2rem;">${escapeHtml(s.phone)}</div>` : ''}
            </div>
          </div>
          <div style="display: flex; gap: 0.35rem;">
            <button class="btn-icon-square" type="button" onclick="editStaff('${s.id}')">✎</button>
            <button class="btn-icon-square" style="color: var(--color-danger);" type="button" onclick="deleteStaffPrompt('${s.id}', '${escapeHtml(s.name)}')">🗑️</button>
          </div>
        </div>
      `;
    }).join('');
  }

  window.editStaff = function (id) {
    const s = DataStore.getStaff().find(st => st.id === id);
    if (s) {
      staffModalTitle.textContent = 'Personel Bilgilerini Düzenle';
      staffFormId.value = s.id;
      staffFormName.value = s.name;
      staffFormRole.value = s.role;
      staffFormStatus.value = s.status;
      staffFormPhone.value = s.phone || '';
      openModal('staffModal');
    }
  };

  window.deleteStaffPrompt = function (id, name) {
    openConfirmModal(
      `"${name}" adlı personeli listeden silmek istiyor musunuz?`,
      'Personel sistemden kaldırılacaktır.',
      function () {
        DataStore.deleteStaff(id);
        showToast(`"${name}" silindi.`, 'success');
        renderStaff();
      }
    );
  };

  if (btnOpenAddStaff) {
    btnOpenAddStaff.addEventListener('click', function () {
      staffModalTitle.textContent = 'Yeni Personel Ekle';
      staffFormId.value = '';
      staffForm.reset();
      openModal('staffModal');
    });
  }

  if (staffForm) {
    staffForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const id = staffFormId.value;
      const name = staffFormName.value.trim();
      const role = staffFormRole.value;
      const status = staffFormStatus.value;
      const phone = staffFormPhone.value.trim();

      if (!name) {
        showToast('Ad Soyad zorunludur.', 'danger');
        return;
      }

      DataStore.saveStaff({
        id: id || undefined,
        name,
        role,
        status,
        phone
      });

      closeModal('staffModal');
      showToast(id ? 'Personel güncellendi.' : 'Yeni personel kaydedildi.', 'success');
      renderStaff();
    });
  }

  /* ==========================================================================
     11. SETTINGS MANAGEMENT
     ========================================================================== */
  const settingCafeName = document.getElementById('settingCafeName');
  const settingSlogan = document.getElementById('settingSlogan');
  const settingPhone = document.getElementById('settingPhone');
  const settingAddress = document.getElementById('settingAddress');
  const settingInstagram = document.getElementById('settingInstagram');
  const settingHours = document.getElementById('settingHours');
  const settingCallWaiter = document.getElementById('settingCallWaiter');
  const settingRequestBill = document.getElementById('settingRequestBill');
  const settingSoundNotification = document.getElementById('settingSoundNotification');
  const settingsForm = document.getElementById('settingsForm');

  function renderSettings() {
    const s = DataStore.getSettings();
    if (settingCafeName) settingCafeName.value = s.cafeName || '';
    if (settingSlogan) settingSlogan.value = s.slogan || '';
    if (settingPhone) settingPhone.value = s.phone || '';
    if (settingAddress) settingAddress.value = s.address || '';
    if (settingInstagram) settingInstagram.value = s.instagram || '';
    if (settingHours) settingHours.value = s.hours || '';
    if (settingCallWaiter) settingCallWaiter.checked = s.callWaiter !== false;
    if (settingRequestBill) settingRequestBill.checked = s.requestBill !== false;
    if (settingSoundNotification) settingSoundNotification.checked = s.soundNotification !== false;
  }

  if (settingsForm) {
    settingsForm.addEventListener('submit', function (e) {
      e.preventDefault();
      DataStore.saveSettings({
        cafeName: settingCafeName.value.trim(),
        slogan: settingSlogan.value.trim(),
        phone: settingPhone.value.trim(),
        address: settingAddress.value.trim(),
        instagram: settingInstagram.value.trim(),
        hours: settingHours.value.trim(),
        callWaiter: settingCallWaiter.checked,
        requestBill: settingRequestBill.checked,
        soundNotification: settingSoundNotification.checked
      });

      showToast('Kafe ayarları başarıyla kaydedildi.', 'success');
    });
  }

  /* ==========================================================================
     12. MODAL & CONFIRM DIALOG CONTROLLER
     ========================================================================== */
  window.openModal = function (modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.add('is-open');
  };

  window.closeModal = function (modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.remove('is-open');
  };

  // Close modals on overlay backdrop click
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === this) {
        this.classList.remove('is-open');
      }
    });
  });

  // Confirmation Modal
  let confirmCallback = null;
  const confirmModal = document.getElementById('confirmModal');
  const confirmModalTitle = document.getElementById('confirmModalTitle');
  const confirmModalDesc = document.getElementById('confirmModalDesc');
  const confirmModalOkBtn = document.getElementById('confirmModalOkBtn');

  window.openConfirmModal = function (title, desc, onConfirm) {
    confirmModalTitle.textContent = title;
    confirmModalDesc.textContent = desc;
    confirmCallback = onConfirm;
    openModal('confirmModal');
  };

  if (confirmModalOkBtn) {
    confirmModalOkBtn.addEventListener('click', function () {
      if (confirmCallback) confirmCallback();
      closeModal('confirmModal');
      confirmCallback = null;
    });
  }

  /* ==========================================================================
     13. TOAST SYSTEM
     ========================================================================== */
  const toastContainer = document.getElementById('toastContainer');

  window.showToast = function (message, type = 'success') {
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = 'toast ' + (type === 'danger' ? 'toast--danger' : 'toast--success');

    const icon = type === 'danger' ? '⚠️' : (type === 'info' ? 'ℹ️' : '✓');
    toast.innerHTML = `
      <span class="toast-icon">${icon}</span>
      <span class="toast-message">${escapeHtml(message)}</span>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  };

  /* ==========================================================================
     14. UTILITIES
     ========================================================================== */
  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function getCategoryName(catId) {
    const cat = DataStore.getCategories().find(c => c.id === catId);
    return cat ? cat.name : catId;
  }

  function refreshAllViews() {
    renderDashboard();
    renderMenu();
    renderCategories();
    renderOptions();
    renderTables();
    renderStaff();
    renderSettings();
  }

  /* ==========================================================================
     15. INITIALIZATION
     ========================================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    checkAuth();
    refreshAllViews();

    // Check URL hash for routing
    const hash = window.location.hash.replace('#', '');
    if (hash && VIEW_TITLES[hash]) {
      navigateTo(hash);
    } else {
      navigateTo('dashboard');
    }

    // NOT: Sunucu verisi Vercel'de geçici (/tmp) olduğundan LocalStorage'ı ezmiyoruz.
    // Tüm değişiklikler LocalStorage'da tutulur; saveAll() sunucuya da yazar (müşteri menüsü için).
  });

})();

