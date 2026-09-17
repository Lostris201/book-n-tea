import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Static export: `npm run build` writes plain HTML/JS/CSS to out/, served by Nginx on the VPS.
  // The menu and staff board fetch everything from the Laravel API at runtime.
  output: "export",
  // /staff → /staff/index.html, so Nginx can serve directories without rewrite rules.
  trailingSlash: true,
  images: { unoptimized: true },
};

export default nextConfig;
