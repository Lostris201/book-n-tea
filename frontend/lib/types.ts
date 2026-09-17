export type MenuProduct = {
  id: string;
  name: string;
  category: string;
  price: number;
  desc: string;
  image: string | null;
  active: boolean;
  bestseller: boolean;
  isNew: boolean;
  customizable: boolean;
};

export type MenuCategory = { id: string; name: string; icon: string | null; order: number };

export type MenuOption = { id: string; name: string; price: number };

export type MenuOptionGroup = { key: string; name: string; multiSelect: boolean };

export type MenuSettings = {
  cafeName: string | null;
  slogan: string | null;
  phone: string | null;
  address: string | null;
  instagram: string | null;
  hours: string | null;
  callWaiter: boolean | null;
  requestBill: boolean | null;
  soundNotification: boolean | null;
};

export type Menu = {
  products: MenuProduct[];
  categories: MenuCategory[];
  options: Record<string, MenuOption[]>;
  productOptionMappings: Record<string, Record<string, boolean>>;
  optionGroups: MenuOptionGroup[];
  settings: MenuSettings;
};

export type ResolvedTable = { number: number; name: string };

export type OrderStatus = "new" | "preparing" | "ready" | "delivered" | "done";

export type OrderItem = {
  productId: string | null;
  name: string;
  options: MenuOption[];
  price: number;
  qty: number;
  isNew: boolean;
};

export type Order = {
  id: string;
  table: string;
  tableName: string;
  items: OrderItem[];
  note: string;
  status: OrderStatus;
  hasNewItems: boolean;
  total: number;
  createdAt: string;
  updatedAt: string;
};

export type WaiterCall = {
  id: number;
  table: string;
  tableName: string;
  type: "waiter" | "bill";
  reason: string | null;
  resolved: boolean;
  createdAt: string;
};

export type StaffUser = { id: number; name: string; email: string; role: "admin" | "manager" | "staff" };
