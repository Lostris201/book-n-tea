import { formatTl } from "@/lib/money";
import type { MenuProduct } from "@/lib/types";

type Props = {
  product: MenuProduct;
  canOrder: boolean;
  cartQty: number;
  onAdd: () => void;
  onCustomize: () => void;
  onIncrement: () => void;
  onDecrement: () => void;
};

export function tagLabels(product: MenuProduct): string[] {
  const labels: string[] = [];
  if (product.bestseller) labels.push("⭐ Bestseller");
  if (product.isNew) labels.push("🆕 Yeni");
  return labels;
}

function tagClass(tag: string) {
  if (tag.includes("Vegan")) return "p-tag p-tag--vegan";
  if (tag.includes("Gluten")) return "p-tag p-tag--gf";
  if (tag.includes("Organik")) return "p-tag p-tag--organic";
  return "p-tag";
}

export default function ProductCard({ product, canOrder, cartQty, onAdd, onCustomize, onIncrement, onDecrement }: Props) {
  return (
    <article className="p-card">
      <div className="p-card__media">
        {product.image && (
          // eslint-disable-next-line @next/next/no-img-element -- images come from the API at runtime (static export)
          <img className="p-card__img" src={product.image} alt={product.name} loading="lazy" />
        )}
        {product.bestseller && (
          <div className="bestseller-badge">
            <span>⭐</span>
            <span>BESTSELLER</span>
          </div>
        )}
      </div>
      <div className="p-card__body">
        <div className="p-card__header">
          <h3 className="p-card__title">{product.name}</h3>
          <span className="p-card__price">{formatTl(product.price)}</span>
        </div>
        <p className="p-card__desc">{product.desc}</p>

        <div className="p-card__tags">
          {tagLabels(product).map((tag) => (
            <span key={tag} className={tagClass(tag)}>
              {tag}
            </span>
          ))}
        </div>

        {canOrder && (
          <div className="p-card__footer">
            {product.customizable ? (
              <button type="button" className="p-card__opt-btn" onClick={onCustomize}>
                Süt &amp; Seçenekler ⚙️
              </button>
            ) : (
              <span />
            )}

            {cartQty > 0 ? (
              <div className="card-counter">
                <button type="button" aria-label="Azalt" onClick={onDecrement}>
                  −
                </button>
                <span>{cartQty}</span>
                <button type="button" aria-label="Artır" onClick={onIncrement}>
                  +
                </button>
              </div>
            ) : (
              <button type="button" className="add-btn" onClick={onAdd}>
                <span>+ Ekle</span>
              </button>
            )}
          </div>
        )}
      </div>
    </article>
  );
}
