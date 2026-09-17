"use client";

import { useState } from "react";
import { formatCents, toCents } from "@/lib/money";
import type { Menu, MenuOption, MenuProduct } from "@/lib/types";
import type { CartLine } from "@/lib/cart";

type Props = {
  product: MenuProduct;
  menu: Menu;
  onClose: () => void;
  onAdd: (line: Omit<CartLine, "key">) => void;
};

type Group = { key: string; name: string; multiSelect: boolean; options: MenuOption[] };

function groupsFor(product: MenuProduct, menu: Menu): Group[] {
  const mapping = menu.productOptionMappings[product.id] ?? {};

  return menu.optionGroups
    .filter((g) => mapping[g.key] && (menu.options[g.key]?.length ?? 0) > 0)
    .map((g) => ({ ...g, options: menu.options[g.key] }));
}

function optionLabel(option: MenuOption) {
  return option.price > 0 ? `${option.name} (+${formatCents(toCents(option.price))})` : option.name;
}

export default function CustomizeModal({ product, menu, onClose, onAdd }: Props) {
  const groups = groupsFor(product, menu);

  // Single-choice groups start on their first option, like the legacy menu.
  const [selected, setSelected] = useState<Record<string, string[]>>(() =>
    Object.fromEntries(groups.map((g) => [g.key, g.multiSelect ? [] : [g.options[0].id]])),
  );
  const [qty, setQty] = useState(1);

  const chosen = groups.flatMap((g) => g.options.filter((o) => selected[g.key]?.includes(o.id)));
  const unitCents = toCents(product.price) + chosen.reduce((sum, o) => sum + toCents(o.price), 0);

  const toggle = (group: Group, optionId: string) => {
    setSelected((current) => {
      if (!group.multiSelect) return { ...current, [group.key]: [optionId] };
      const list = current[group.key] ?? [];
      return { ...current, [group.key]: list.includes(optionId) ? list.filter((id) => id !== optionId) : [...list, optionId] };
    });
  };

  const confirm = () => {
    onAdd({
      productId: product.id,
      name: product.name,
      optionIds: chosen.map((o) => o.id),
      optionsLabel: chosen.map((o) => o.name).join(", "),
      unitCents,
      qty,
    });
    onClose();
  };

  return (
    <div className="modal">
      <div className="modal__backdrop" onClick={onClose} />
      <div className="modal__dialog" role="dialog" aria-label={product.name}>
        <button type="button" className="modal__close" onClick={onClose} aria-label="Kapat">
          ✕
        </button>

        <div className="custom-header">
          {product.image && (
            // eslint-disable-next-line @next/next/no-img-element -- runtime API image
            <img src={product.image} alt="" className="custom-header__img" />
          )}
          <div className="custom-header__info">
            <h3 className="custom-header__title">{product.name}</h3>
            <p className="custom-header__desc">{product.desc}</p>
            <div className="custom-header__price">{formatCents(toCents(product.price))}</div>
          </div>
        </div>

        <div className="custom-options">
          {groups.map((group) => (
            <div className="custom-group" key={group.key}>
              <h4 className="custom-group__title">{group.name}</h4>
              <div className="custom-group__options">
                {group.options.map((option) => (
                  <label className="opt-btn" key={option.id}>
                    <input
                      type={group.multiSelect ? "checkbox" : "radio"}
                      name={`opt-${group.key}`}
                      checked={selected[group.key]?.includes(option.id) ?? false}
                      onChange={() => toggle(group, option.id)}
                    />
                    <span>{optionLabel(option)}</span>
                  </label>
                ))}
              </div>
            </div>
          ))}
        </div>

        <div className="custom-footer">
          <div className="quantity-stepper">
            <button type="button" className="stepper-btn" onClick={() => setQty((q) => Math.max(1, q - 1))}>
              −
            </button>
            <span className="stepper-val">{qty}</span>
            <button type="button" className="stepper-btn" onClick={() => setQty((q) => Math.min(50, q + 1))}>
              +
            </button>
          </div>
          <button type="button" className="btn-primary" onClick={confirm}>
            <span>Siparişe Ekle • {formatCents(unitCents * qty)}</span>
          </button>
        </div>
      </div>
    </div>
  );
}
