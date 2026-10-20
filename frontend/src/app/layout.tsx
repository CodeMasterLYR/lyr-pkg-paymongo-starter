import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "PayMongo Playground — LYR Starter",
  description: "Open-source Next.js + Laravel test playground for PayMongo GCash, Maya, QR Ph, and Card integrations.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>
        <nav>
          <div className="container">
            <span style={{ display: "flex", alignItems: "center" }}>
              <a className="nav-brand" href="/">⚡ PayMongo Playground</a>
              <span className="nav-tag">LYR Open Source</span>
            </span>
            <span className="text-muted text-sm">Laravel + Next.js Starter</span>
          </div>
        </nav>
        <main className="container" style={{ paddingBottom: 48 }}>
          {children}
        </main>
      </body>
    </html>
  );
}
