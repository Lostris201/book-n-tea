import { Suspense } from "react";
import MenuApp from "@/components/menu/MenuApp";
import Loader from "@/components/menu/Loader";

export default function MenuPage() {
  // MenuApp reads ?t= from the URL, which is only known in the browser.
  return (
    <Suspense fallback={<Loader done={false} />}>
      <MenuApp />
    </Suspense>
  );
}
