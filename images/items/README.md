# Menu item photos

Each menu item in `index.html` has a thumbnail slot that references an
image at `images/items/<slug>.jpg` by filename convention — no code
change needed to add a real photo, just drop the correctly-named file
here. Until a file exists, the slot shows a plain placeholder (no
broken-image icon, since it's a CSS `background-image`, not `<img>`).

Slug = the item's name, lowercased, Turkish characters transliterated,
spaces and `&` replaced with `-`.

Recommended: square-ish photos, at least 300×300px, JPG.

| Category      | Item             | Expected filename       |
| -------------- | ---------------- | ------------------------ |
| Çay            | Earl Grey         | `earl-grey.jpg`          |
| Çay            | Yeşil Çay         | `yesil-cay.jpg`          |
| Çay            | Papatya           | `papatya.jpg`            |
| Çay            | Adaçayı & Ballı   | `adacayi-balli.jpg`      |
| Çay            | Chai Latte        | `chai-latte.jpg`         |
| Kahve          | Espresso          | `espresso.jpg`           |
| Kahve          | Americano         | `americano.jpg`          |
| Kahve          | Latte             | `latte.jpg`              |
| Kahve          | Cappuccino        | `cappuccino.jpg`         |
| Kahve          | Filtre Kahve      | `filtre-kahve.jpg`       |
| Tatlılar       | Cheesecake        | `cheesecake.jpg`         |
| Tatlılar       | Brownie           | `brownie.jpg`            |
| Tatlılar       | Cookie            | `cookie.jpg`             |
| Tatlılar       | Tiramisu          | `tiramisu.jpg`           |
| Atıştırmalık   | Avokadolu Toast   | `avokadolu-toast.jpg`    |
| Atıştırmalık   | Peynir Tabağı     | `peynir-tabagi.jpg`      |
| Atıştırmalık   | Granola Bowl      | `granola-bowl.jpg`       |
| Atıştırmalık   | Sandviç           | `sandvic.jpg`            |
