/* ==========================================================================
   BOOK & TEA HOUSE — Menu & Order Application JavaScript
   ========================================================================== */

// 1. Comprehensive Menu Dataset
const MENU_DATA = [
  // --- Özel Çaylar ---
  {
    id: "tea_1",
    name: "Earl Grey Royal",
    category: "tea",
    price: 95,
    desc: "Bergamot harmanlı siyah çay, kurutulmuş peygamber çiçeği ve portakal kabuğu ile demlenmiş kraliyet serisi.",
    image: "assets/earl_grey.png",
    bestseller: true,
    tags: ["organik", "hot"],
    tagLabels: ["🍃 Organik", "🔥 Sıcak"],
    customizable: true,
  },
  {
    id: "tea_2",
    name: "Japon Sencha Yeşil Çay",
    category: "tea",
    price: 90,
    desc: "Birinci hasat Japon yeşil çay yaprakları; hafif ferahlatıcı ve antioksidan deposu.",
    image: "assets/earl_grey.png",
    bestseller: false,
    tags: ["organik", "vegan", "hot"],
    tagLabels: ["🍃 Organik", "🌱 Vegan"],
    customizable: false,
  },
  {
    id: "tea_3",
    name: "Papatya & Fransız Lavantası",
    category: "tea",
    price: 85,
    desc: "Dinlendirici organik papatya tomurcukları ve rahatlatıcı lavanta aroması.",
    image: "assets/earl_grey.png",
    bestseller: false,
    tags: ["organik", "hot"],
    tagLabels: ["🍃 Organik", "Kafeinsiz"],
    customizable: false,
  },
  {
    id: "tea_4",
    name: "Chai Tea Latte",
    category: "tea",
    price: 115,
    desc: "Geleneksel Hint baharatları, demlenmiş aromatik siyah çay ve buharda ısıtılmış süt köpüğü.",
    image: "assets/earl_grey.png",
    bestseller: true,
    tags: ["hot"],
    tagLabels: ["⭐ Bestseller", "🔥 Sıcak"],
    customizable: true,
  },
  {
    id: "tea_5",
    name: "Buzlu Şeftali & Hibiskus",
    category: "tea",
    price: 105,
    desc: "Ev yapımı organik şeftali püresi, soğuk demlenmiş ekşi hibiskus çayı ve taze nane.",
    image: "assets/earl_grey.png",
    bestseller: false,
    tags: ["cold", "vegan"],
    tagLabels: ["❄️ Soğuk", "🌱 Vegan"],
    customizable: false,
  },

  // --- Kahve Sanatı ---
  {
    id: "coff_1",
    name: "Kütüphane Özel Latte",
    category: "coffee",
    price: 120,
    desc: "Çift shot nitelikli Kolombiya espresso, yulaf sütlü kadifemsi doku ve hafif karamel lezzeti.",
    image: "assets/latte.png",
    bestseller: true,
    tags: ["hot"],
    tagLabels: ["⭐ Bestseller", "☕ Özel Harman"],
    customizable: true,
  },
  {
    id: "coff_2",
    name: "Double Ristretto Espresso",
    category: "coffee",
    price: 75,
    desc: "%100 Arabica nitelikli harman, kısa ve yoğun aroma.",
    image: "assets/latte.png",
    bestseller: false,
    tags: ["hot"],
    tagLabels: ["Yoğun Aroma"],
    customizable: false,
  },
  {
    id: "coff_3",
    name: "Velvet Cappuccino",
    category: "coffee",
    price: 110,
    desc: "Dengeli espresso bazı ve ipeksi kıvamda mikro süt köpüğü.",
    image: "assets/latte.png",
    bestseller: false,
    tags: ["hot"],
    tagLabels: ["🔥 Sıcak", "Klasik"],
    customizable: true,
  },
  {
    id: "coff_4",
    name: "Cold Brew Reserve",
    category: "coffee",
    price: 125,
    desc: "Etiyopya Yirgacheffe çekirdeklerinden 18 saat soğuk demlenmiş yumuşak içimli nitelikli kahve.",
    image: "assets/latte.png",
    bestseller: true,
    tags: ["cold", "vegan"],
    tagLabels: ["❄️ Soğuk", "🌱 Vegan"],
    customizable: false,
  },

  // --- Kütüphane Fırını ---
  {
    id: "bakery_1",
    name: "Avokadolu Ekşi Maya Toast",
    category: "bakery",
    price: 165,
    desc: "Kendi fırınımızdan çıkan kızarmış ekşi maya ekmek, sızma zeytinyağlı avokado pürüzü, poşe yumurta ve mikro yeşillikler.",
    image: "assets/avocado_toast.png",
    bestseller: true,
    tags: ["vegan", "glutenfree"],
    tagLabels: ["⭐ Bestseller", "🌾 Gluten-Free Seçenek"],
    customizable: false,
  },
  {
    id: "bakery_2",
    name: "Tereyağlı Fransız Kruvasanı",
    category: "bakery",
    price: 85,
    desc: "Fransız tereyağı ile hazırlanan, kat kat çıtır ve yumuşak taze fırınlanmış kruvasan.",
    image: "assets/avocado_toast.png",
    bestseller: false,
    tags: ["hot"],
    tagLabels: ["🥐 Taze Fırın"],
    customizable: false,
  },

  // --- Tatlılar ---
  {
    id: "dessert_1",
    name: "San Sebastian Cheesecake",
    category: "dessert",
    price: 155,
    desc: "İspanyol usulü fırınlanmış yanık üzeri ve kremsi akışkan içi ile; yanında taze karadut kompostosu.",
    image: "assets/cheesecake.png",
    bestseller: true,
    tags: ["bestseller"],
    tagLabels: ["⭐ Bestseller", "🍰 Ev Yapımı"],
    customizable: false,
  },
  {
    id: "dessert_2",
    name: "Sıcak Çikolatalı Brownie",
    category: "dessert",
    price: 135,
    desc: "%70 Belçika çikolatası ve kıyılmış ceviz içi ile hazırlanan sıcak servis dilim.",
    image: "assets/cheesecake.png",
    bestseller: false,
    tags: ["hot"],
    tagLabels: ["🔥 Sıcak Servis"],
    customizable: false,
  },
  {
    id: "dessert_3",
    name: "Geleneksel Tiramisu",
    category: "dessert",
    price: 160,
    desc: "Mascarpone peynirli hafif krema ve espressolu kedi dili bisküvileri katmanları.",
    image: "assets/cheesecake.png",
    bestseller: false,
    tags: ["cold"],
    tagLabels: ["İtalyan Reçetesi"],
    customizable: false,
  },

  // --- Sandviç & Tost ---
  {
    id: "sand_1",
    name: "Gurme Peynir & Şarküteri Tabağı",
    category: "sandwich",
    price: 195,
    desc: "Üç çeşit olgunlaştırılmış peynir, ceviz içi, kuru incir, kovan balı ve çıtır kıtır ekmekler.",
    image: "assets/avocado_toast.png",
    bestseller: false,
    tags: ["bestseller"],
    tagLabels: ["Paylaşımlık"],
    customizable: false,
  },
  {
    id: "sand_2",
    name: "Fesleğenli Mozzarella Panini",
    category: "sandwich",
    price: 160,
    desc: "Sıcak ciabatta ekmeğinde erimiş taze mozzarella, domates ve ev yapımı fesleğen pesto sosu.",
    image: "assets/avocado_toast.png",
    bestseller: false,
    tags: ["hot"],
    tagLabels: ["Vejetaryen"],
    customizable: false,
  },

  // --- Kitap & Merch ---
  {
    id: "book_1",
    name: "Book & Tea Seramik Fincan",
    category: "books",
    price: 250,
    desc: "Özel tasarım el yapımı toprak mat seramik fincan. Logolu özel kutusunda.",
    image: "assets/hero.png",
    bestseller: false,
    tags: [],
    tagLabels: ["Özel Tasarım"],
    customizable: false,
  },
  {
    id: "book_2",
    name: "Deri Kitap Ayracı & Not Defteri",
    category: "books",
    price: 180,
    desc: "Hakiki deri kitap ayracı ve noktalı kütüphane not defteri seti.",
    image: "assets/hero.png",
    bestseller: false,
    tags: [],
    tagLabels: ["Hediye Seti"],
    customizable: false,
  },
];

// 2. Global Application State
// Admin panelinden kaydedilen veriyi yükle, yoksa MENU_DATA'yı kullan
function loadMenuData() {
  try {
    const raw = localStorage.getItem('bnt_admin_data_v1');
    if (!raw) return MENU_DATA;
    const adminData = JSON.parse(raw);
    if (!adminData.products || adminData.products.length === 0) return MENU_DATA;

    // Sadece aktif ürünleri filtrele, script formatına dönüştür
    const mapped = adminData.products
      .filter((p) => p.active !== false)
      .map((p) => ({
        id: p.id,
        name: p.name,
        category: p.category,
        price: Number(p.price) || 0,
        desc: p.desc || '',
        image: p.image || 'assets/hero.png',
        bestseller: p.bestseller || false,
        tags: p.tags || [],
        tagLabels: buildTagLabels(p),
        customizable: p.customizable || false,
      }));

    return mapped.length > 0 ? mapped : MENU_DATA;
  } catch (e) {
    console.warn('Admin veri yüklenemedi, varsayılan menü kullanılıyor.', e);
    return MENU_DATA;
  }
}

// Admin'de tagLabels yoksa basit bir etiket seti oluştur
function buildTagLabels(p) {
  if (p.tagLabels && p.tagLabels.length > 0) return p.tagLabels;
  const labels = [];
  if (p.bestseller) labels.push('⭐ Bestseller');
  if (p.isNew) labels.push('🆕 Yeni');
  return labels;
}

const state = {
  tableNumber: "04",
  cart: new Map(), // key: unique item id or string, val: item object
  currentCategory: "all",
  currentTag: "all",
  searchQuery: "",
  selectedProductForCustom: null,
  customQty: 1,
  activeOrder: null,
  menuData: loadMenuData(), // Admin panelinden senkronize edilmiş menü
};


// 3. DOM Element References
const loader = document.getElementById("loader");
const tableBadgeBtn = document.getElementById("tableBadgeBtn");
const tableDisplayLabel = document.getElementById("tableDisplayLabel");
const searchInput = document.getElementById("searchInput");
const searchClearBtn = document.getElementById("searchClearBtn");
const tagChips = document.getElementById("tagChips");
const categoriesScroll = document.getElementById("categoriesScroll");
const productGrid = document.getElementById("productGrid");
const resultsCount = document.getElementById("resultsCount");
const emptySearch = document.getElementById("emptySearch");
const resetSearchBtn = document.getElementById("resetSearchBtn");

// Cart Bar & Drawer
const cartBar = document.getElementById("cartBar");
const cartBarCount = document.getElementById("cartBarCount");
const cartBarTotal = document.getElementById("cartBarTotal");
const openCartBtn = document.getElementById("openCartBtn");
const cartDrawer = document.getElementById("cartDrawer");
const cartBackdrop = document.getElementById("cartBackdrop");
const closeCartBtn = document.getElementById("closeCartBtn");
const cartItemsList = document.getElementById("cartItemsList");
const cartTableBadge = document.getElementById("cartTableBadge");
const cartSubtotal = document.getElementById("cartSubtotal");
const cartGrandTotal = document.getElementById("cartGrandTotal");
const submitOrderBtn = document.getElementById("submitOrderBtn");
const cartErrorMsg = document.getElementById("cartErrorMsg");
const orderNoteInput = document.getElementById("orderNoteInput");

// Modals
const customModal = document.getElementById("customModal");
const customBackdrop = document.getElementById("customBackdrop");
const closeCustomModal = document.getElementById("closeCustomModal");
const customModalImg = document.getElementById("customModalImg");
const customModalTitle = document.getElementById("customModalTitle");
const customModalDesc = document.getElementById("customModalDesc");
const customModalPrice = document.getElementById("customModalPrice");
const customDecBtn = document.getElementById("customDecBtn");
const customIncBtn = document.getElementById("customIncBtn");
const customQtyVal = document.getElementById("customQtyVal");
const confirmCustomAddBtn = document.getElementById("confirmCustomAddBtn");
const customAddBtnLabel = document.getElementById("customAddBtnLabel");
const milkOptionGroup = document.getElementById("milkOptionGroup");

const tableModal = document.getElementById("tableModal");
const tableBackdrop = document.getElementById("tableBackdrop");
const tableGridOptions = document.getElementById("tableGridOptions");
const customTableInput = document.getElementById("customTableInput");
const saveCustomTableBtn = document.getElementById("saveCustomTableBtn");

const waiterModal = document.getElementById("waiterModal");
const waiterBackdrop = document.getElementById("waiterBackdrop");
const callWaiterBtn = document.getElementById("callWaiterBtn");
const closeWaiterModal = document.getElementById("closeWaiterModal");

const receiptModal = document.getElementById("receiptModal");
const closeReceiptBtn = document.getElementById("closeReceiptBtn");
const receiptId = document.getElementById("receiptId");
const receiptTable = document.getElementById("receiptTable");
const receiptItemsList = document.getElementById("receiptItemsList");
const receiptTotalVal = document.getElementById("receiptTotalVal");

const toast = document.getElementById("toast");
const toastMsg = document.getElementById("toastMsg");
const toastIcon = document.getElementById("toastIcon");

let toastTimer = null;

// 4. Initialization Logic
window.addEventListener("DOMContentLoaded", () => {
  initTableNumber();
  renderTableGridOptions();
  renderProducts();

  // Hide loader with smooth fade after loading assets
  setTimeout(() => {
    loader.classList.add("is-done");
  }, 1000);
});

// Parse Table number from URL query string ?masa= or ?table=
function initTableNumber() {
  const params = new URLSearchParams(window.location.search);
  const masa = params.get("masa") || params.get("table");
  if (masa && /^\d+$/.test(masa)) {
    state.tableNumber = masa.padStart(2, "0");
  } else {
    state.tableNumber = "04"; // Default demonstration table
  }
  updateTableDisplay();
}

function updateTableDisplay() {
  tableDisplayLabel.textContent = `Masa ${state.tableNumber}`;
  cartTableBadge.textContent = `Masa ${state.tableNumber}`;
}

// 5. Render Product Cards Grid
function renderProducts() {
  const query = state.searchQuery.trim().toLowerCase();

  const filtered = state.menuData.filter((item) => {
    // Category match
    const catMatch = state.currentCategory === "all" || item.category === state.currentCategory;

    // Tag match
    let tagMatch = true;
    if (state.currentTag === "bestseller") tagMatch = item.bestseller === true;
    else if (state.currentTag === "vegan") tagMatch = item.tags.includes("vegan");
    else if (state.currentTag === "glutenfree") tagMatch = item.tags.includes("glutenfree");
    else if (state.currentTag === "hot") tagMatch = item.tags.includes("hot");
    else if (state.currentTag === "cold") tagMatch = item.tags.includes("cold");

    // Text search query match
    const textMatch = !query ||
      item.name.toLowerCase().includes(query) ||
      item.desc.toLowerCase().includes(query) ||
      item.tagLabels.some((t) => t.toLowerCase().includes(query));

    return catMatch && tagMatch && textMatch;
  });

  // Render Status Count Text
  if (filtered.length === 0) {
    productGrid.innerHTML = "";
    emptySearch.hidden = false;
    resultsCount.textContent = "0 lezzet bulundu";
    return;
  }

  emptySearch.hidden = true;
  resultsCount.textContent = `${filtered.length} lezzet listeleniyor`;

  // Render Product Cards
  productGrid.innerHTML = filtered
    .map((item) => {
      const cartQty = getItemCartQty(item.id);

      const tagsHtml = item.tagLabels
        .map((tag) => {
          let extraClass = "";
          if (tag.includes("Vegan")) extraClass = "p-tag--vegan";
          if (tag.includes("Gluten")) extraClass = "p-tag--gf";
          if (tag.includes("Organik")) extraClass = "p-tag--organic";
          return `<span class="p-tag ${extraClass}">${escapeHtml(tag)}</span>`;
        })
        .join("");

      return `
        <article class="p-card" data-id="${item.id}">
          <div class="p-card__media">
            <img class="p-card__img" src="${item.image}" alt="${escapeHtml(item.name)}" loading="lazy" />
            ${item.bestseller ? `<div class="bestseller-badge"><span>⭐</span><span>BESTSELLER</span></div>` : ""}
          </div>
          <div class="p-card__body">
            <div class="p-card__header">
              <h3 class="p-card__title">${escapeHtml(item.name)}</h3>
              <span class="p-card__price">${item.price} ₺</span>
            </div>
            <p class="p-card__desc">${escapeHtml(item.desc)}</p>
            
            <div class="p-card__tags">
              ${tagsHtml}
            </div>

            <div class="p-card__footer">
              ${item.customizable ? `<button type="button" class="p-card__opt-btn" data-act="customize" data-id="${item.id}">Süt & Seçenekler ⚙️</button>` : `<span></span>`}
              
              ${cartQty > 0
          ? `
                <div class="card-counter">
                  <button type="button" data-act="dec-card" data-id="${item.id}" aria-label="Azalt">−</button>
                  <span>${cartQty}</span>
                  <button type="button" data-act="inc-card" data-id="${item.id}" aria-label="Artır">+</button>
                </div>
              `
          : `
                <button type="button" class="add-btn" data-act="add-quick" data-id="${item.id}">
                  <span>+ Ekle</span>
                </button>
              `
        }
            </div>
          </div>
        </article>
      `;
    })
    .join("");
}

// 6. Search Bar & Tag Chip Event Listeners
searchInput.addEventListener("input", (e) => {
  state.searchQuery = e.target.value;
  searchClearBtn.hidden = !state.searchQuery;
  renderProducts();
});

searchClearBtn.addEventListener("click", () => {
  searchInput.value = "";
  state.searchQuery = "";
  searchClearBtn.hidden = true;
  renderProducts();
});

resetSearchBtn?.addEventListener("click", () => {
  searchInput.value = "";
  state.searchQuery = "";
  state.currentCategory = "all";
  state.currentTag = "all";
  searchClearBtn.hidden = true;

  document.querySelectorAll(".cat-pill").forEach((b) => b.classList.toggle("is-active", b.dataset.cat === "all"));
  document.querySelectorAll(".tag-chip").forEach((b) => b.classList.toggle("is-active", b.dataset.tag === "all"));

  renderProducts();
});

tagChips.addEventListener("click", (e) => {
  const chip = e.target.closest(".tag-chip");
  if (!chip) return;

  tagChips.querySelectorAll(".tag-chip").forEach((b) => b.classList.remove("is-active"));
  chip.classList.add("is-active");

  state.currentTag = chip.dataset.tag;
  renderProducts();
});

// Category Pills Navigation
categoriesScroll.addEventListener("click", (e) => {
  const pill = e.target.closest(".cat-pill");
  if (!pill) return;

  categoriesScroll.querySelectorAll(".cat-pill").forEach((b) => b.classList.remove("is-active"));
  pill.classList.add("is-active");

  state.currentCategory = pill.dataset.cat;
  renderProducts();
});

// 7. Product Grid Card Actions (Add, Customize, Increment, Decrement)
productGrid.addEventListener("click", (e) => {
  const addQuickBtn = e.target.closest('[data-act="add-quick"]');
  const custBtn = e.target.closest('[data-act="customize"]');
  const incBtn = e.target.closest('[data-act="inc-card"]');
  const decBtn = e.target.closest('[data-act="dec-card"]');

  if (addQuickBtn) {
    const id = addQuickBtn.dataset.id;
    const product = state.menuData.find((p) => p.id === id);
    if (!product) return;

    if (product.customizable) {
      openCustomizationModal(product);
    } else {
      addToCart(product.id, product.name, product.price, 1, "");
    }
  } else if (custBtn) {
    const id = custBtn.dataset.id;
    const product = state.menuData.find((p) => p.id === id);
    if (product) openCustomizationModal(product);
  } else if (incBtn) {
    const id = incBtn.dataset.id;
    updateCartItemQty(id, 1);
  } else if (decBtn) {
    const id = decBtn.dataset.id;
    updateCartItemQty(id, -1);
  }
});

// 8. Customization Modal Logic
function openCustomizationModal(product) {
  state.selectedProductForCustom = product;
  state.customQty = 1;

  customModalImg.src = product.image;
  customModalTitle.textContent = product.name;
  customModalDesc.textContent = product.desc;
  customModalPrice.textContent = `${product.price} ₺`;
  customQtyVal.textContent = "1";

  // Hide or show milk choices
  milkOptionGroup.hidden = !product.category.includes("tea") && !product.category.includes("coffee");

  updateCustomModalTotal();
  customModal.hidden = false;
}

function updateCustomModalTotal() {
  if (!state.selectedProductForCustom) return;
  const base = state.selectedProductForCustom.price;

  let extra = 0;
  const checkedMilk = document.querySelector('input[name="milkOpt"]:checked');
  if (checkedMilk && checkedMilk.dataset.extra) {
    extra += Number(checkedMilk.dataset.extra);
  }

  const unitPrice = base + extra;
  const totalPrice = unitPrice * state.customQty;
  customAddBtnLabel.textContent = `Siparişe Ekle • ${totalPrice} ₺`;
}

document.querySelectorAll('input[name="milkOpt"]').forEach((radio) => {
  radio.addEventListener("change", updateCustomModalTotal);
});

customDecBtn.addEventListener("click", () => {
  if (state.customQty > 1) {
    state.customQty--;
    customQtyVal.textContent = String(state.customQty);
    updateCustomModalTotal();
  }
});

customIncBtn.addEventListener("click", () => {
  state.customQty++;
  customQtyVal.textContent = String(state.customQty);
  updateCustomModalTotal();
});

confirmCustomAddBtn.addEventListener("click", () => {
  const p = state.selectedProductForCustom;
  if (!p) return;

  const milkOpt = document.querySelector('input[name="milkOpt"]:checked')?.value || "";
  const sugarOpt = document.querySelector('input[name="sugarOpt"]:checked')?.value || "";

  let extra = 0;
  const checkedMilk = document.querySelector('input[name="milkOpt"]:checked');
  if (checkedMilk && checkedMilk.dataset.extra) {
    extra += Number(checkedMilk.dataset.extra);
  }

  const finalUnitPrice = p.price + extra;
  const optsSummary = [milkOpt, sugarOpt].filter(Boolean).join(", ");

  addToCart(p.id, p.name, finalUnitPrice, state.customQty, optsSummary);
  closeCustomModalFunc();
});

function closeCustomModalFunc() {
  customModal.hidden = true;
  state.selectedProductForCustom = null;
}

closeCustomModal.addEventListener("click", closeCustomModalFunc);
customBackdrop.addEventListener("click", closeCustomModalFunc);

// 9. Cart Operations & Logic
function addToCart(idOrObj, name, price, qty = 1, options = "") {
  let id, itemOptions = options;
  if (typeof idOrObj === "object" && idOrObj !== null) {
    id = idOrObj.id || idOrObj.key || "item";
    name = idOrObj.name || "Ürün";
    price = Number(idOrObj.price) || 0;
    qty = Number(idOrObj.qty) || 1;
    itemOptions = idOrObj.options || "";
  } else {
    id = idOrObj;
    price = Number(price) || 0;
    qty = Number(qty) || 1;
  }

  const cartKey = itemOptions ? `${id}_${itemOptions}` : id;
  const existing = state.cart.get(cartKey);

  if (existing) {
    existing.qty += qty;
  } else {
    state.cart.set(cartKey, {
      key: cartKey,
      id,
      name: String(name || "Ürün"),
      price,
      qty,
      options: itemOptions,
    });
  }

  renderCartUI();
  renderProducts(); // Update card button states
}

function updateCartItemQty(cartKey, delta) {
  // If exact cartKey not found, find first matching key starting with id
  let item = state.cart.get(cartKey);
  if (!item) {
    for (const [key, val] of state.cart.entries()) {
      if (val.id === cartKey) {
        item = val;
        cartKey = key;
        break;
      }
    }
  }

  if (!item) return;

  item.qty += delta;
  if (item.qty <= 0) {
    state.cart.delete(cartKey);
  }

  renderCartUI();
  renderProducts();
}

function getCartTotals() {
  let count = 0;
  let total = 0;

  state.cart.forEach((item) => {
    count += item.qty;
    total += item.price * item.qty;
  });

  return { count, total };
}

function getItemCartQty(productId) {
  let count = 0;
  state.cart.forEach((item) => {
    if (item.id === productId) count += item.qty;
  });
  return count;
}

function renderCartUI() {
  const { count, total } = getCartTotals();

  cartBarCount.textContent = String(count);
  cartBarTotal.textContent = `${total} ₺`;
  cartSubtotal.textContent = `${total} ₺`;
  cartGrandTotal.textContent = `${total} ₺`;

  cartBar.hidden = count === 0;

  // Render Drawer List
  if (state.cart.size === 0) {
    cartItemsList.innerHTML = `<li class="cart-empty-msg" style="text-align:center; color:var(--cream-dim); padding:2rem 0;">Sepetiniz henüz boş.</li>`;
  } else {
    cartItemsList.innerHTML = Array.from(state.cart.values())
      .map(
        (item) => `
        <li class="cart-item">
          <div class="cart-item__info">
            <h4 class="cart-item__title">${escapeHtml(item.name)}</h4>
            ${item.options ? `<p class="cart-item__opts">${escapeHtml(item.options)}</p>` : ""}
            <div class="cart-item__price">${item.price * item.qty} ₺</div>
          </div>
          <div class="cart-item__controls">
            <button type="button" data-act="dec-drawer" data-key="${item.key}">−</button>
            <span>${item.qty}</span>
            <button type="button" data-act="inc-drawer" data-key="${item.key}">+</button>
          </div>
        </li>
      `
      )
      .join("");
  }
}

cartItemsList.addEventListener("click", (e) => {
  const incBtn = e.target.closest('[data-act="inc-drawer"]');
  const decBtn = e.target.closest('[data-act="dec-drawer"]');

  if (incBtn) {
    updateCartItemQty(incBtn.dataset.key, 1);
  } else if (decBtn) {
    updateCartItemQty(decBtn.dataset.key, -1);
  }
});

// 10. Drawer Open / Close Logic
openCartBtn.addEventListener("click", () => {
  cartErrorMsg.hidden = true;
  cartDrawer.hidden = false;
});

function closeCartDrawer() {
  cartDrawer.hidden = true;
}

closeCartBtn.addEventListener("click", closeCartDrawer);
cartBackdrop.addEventListener("click", closeCartDrawer);

// 11. Order Submission API Call to Backend (/api/orders)
submitOrderBtn.addEventListener("click", async () => {
  if (state.cart.size === 0) {
    showCartError("Sepetinizde ürün bulunmuyor.");
    return;
  }

  const note = orderNoteInput.value.trim();

  const items = Array.from(state.cart.values()).map((item) => ({
    name: item.options ? `${item.name} (${item.options})` : item.name,
    price: item.price,
    qty: item.qty,
  }));

  submitOrderBtn.disabled = true;
  submitOrderBtn.querySelector(".btn-primary__text").textContent = "Gönderiliyor...";
  cartErrorMsg.hidden = true;

  try {
    const res = await fetch("/api/orders", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        table: state.tableNumber,
        items,
        note,
      }),
    });

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}));
      throw new Error(errData.error || "Sipariş gönderilemedi.");
    }

    const orderData = await res.json();
    state.activeOrder = orderData;

    // Show Confirmation Receipt
    showReceiptModal(orderData);

    // Clear Cart State
    state.cart.clear();
    orderNoteInput.value = "";
    renderCartUI();
    renderProducts();
    closeCartDrawer();
  } catch (err) {
    showCartError(err.message || "Bağlantı hatası. Sunucunun çalıştığından emin olun.");
  } finally {
    submitOrderBtn.disabled = false;
    submitOrderBtn.querySelector(".btn-primary__text").textContent = "Siparişi Masaya Gönder";
  }
});

function showCartError(msg) {
  cartErrorMsg.textContent = msg;
  cartErrorMsg.hidden = false;
}

// 12. Receipt Modal Generator
function showReceiptModal(order) {
  receiptId.textContent = `#${order.id || "ord_" + Date.now().toString().slice(-6)}`;
  receiptTable.textContent = `Masa ${order.table || state.tableNumber}`;

  let totalSum = 0;
  receiptItemsList.innerHTML = order.items
    .map((item) => {
      const lineTotal = item.price * item.qty;
      totalSum += lineTotal;
      return `
      <div class="receipt-item-line">
        <span>${item.qty}x ${escapeHtml(item.name)}</span>
        <strong>${lineTotal} ₺</strong>
      </div>
    `;
    })
    .join("");

  receiptTotalVal.textContent = `${totalSum} ₺`;
  receiptModal.hidden = false;
}

closeReceiptBtn.addEventListener("click", () => {
  receiptModal.hidden = true;
});

// 13. Table Switcher Dialog
tableBadgeBtn.addEventListener("click", () => {
  tableModal.hidden = false;
});

function renderTableGridOptions() {
  let html = "";
  for (let i = 1; i <= 12; i++) {
    const numStr = String(i).padStart(2, "0");
    const activeClass = state.tableNumber === numStr ? "is-active" : "";
    html += `<button type="button" class="table-opt-btn ${activeClass}" data-num="${numStr}">Masa ${i}</button>`;
  }
  tableGridOptions.innerHTML = html;
}

tableGridOptions.addEventListener("click", (e) => {
  const btn = e.target.closest(".table-opt-btn");
  if (!btn) return;

  state.tableNumber = btn.dataset.num;
  updateTableDisplay();
  renderTableGridOptions();
  tableModal.hidden = true;
});

saveCustomTableBtn.addEventListener("click", () => {
  const val = customTableInput.value.trim();
  if (val && /^\d+$/.test(val)) {
    state.tableNumber = val.padStart(2, "0");
    updateTableDisplay();
    renderTableGridOptions();
    tableModal.hidden = true;
    customTableInput.value = "";
  }
});

tableBackdrop.addEventListener("click", () => {
  tableModal.hidden = true;
});

// 14. Waiter Request Handler
callWaiterBtn.addEventListener("click", () => {
  waiterModal.hidden = false;
});

closeWaiterModal.addEventListener("click", () => {
  waiterModal.hidden = true;
});
waiterBackdrop.addEventListener("click", () => {
  waiterModal.hidden = true;
});

waiterModal.querySelectorAll(".waiter-option-btn").forEach((btn) => {
  btn.addEventListener("click", async () => {
    const reason = btn.dataset.reason;
    waiterModal.hidden = true;

    // Submit notification to orders API as service request
    try {
      await fetch("/api/orders", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          table: state.tableNumber,
          items: [{ name: `[GARSON ÇAĞRISI] ${reason}`, price: 0, qty: 1 }],
          note: "Garson çağrı bildirimi",
        }),
      });
    } catch (e) {
      // Ignore background notification errors
    }
  });
});

// 15. Helper Utilities
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}
