"use client";

import { useState, useEffect, useCallback } from "react";
import { authApi, paymongoApi, type User, type Payment } from "@/lib/api";

// ─── helpers ──────────────────────────────────────────────────────────────
const pesos = (centavos: number) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(centavos / 100);

const badgeClass = (status: string) =>
  status === "paid"    ? "badge badge-success" :
  status === "failed"  ? "badge badge-danger"  :
  status === "pending" ? "badge badge-warning"  : "badge badge-default";

// ─── Main Page ─────────────────────────────────────────────────────────────
export default function Home() {
  const [user, setUser]   = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // Auth form
  const [authMode, setAuthMode] = useState<"login" | "register">("login");
  const [authForm, setAuthForm] = useState({ name: "", email: "", password: "", password_confirmation: "" });
  const [authLoading, setAuthLoading] = useState(false);
  const [authError, setAuthError]     = useState("");

  // Checkout form
  const [items, setItems] = useState([{ name: "Sample Product", amount: "50000", quantity: "1" }]);
  const [description, setDescription] = useState("LYR PayMongo Test Checkout");
  const [checkoutLoading, setCheckoutLoading] = useState(false);
  const [checkoutError, setCheckoutError]     = useState("");

  // Payments list
  const [payments, setPayments]             = useState<Payment[]>([]);
  const [paymentsLoading, setPaymentsLoading] = useState(false);

  // On mount — check if session cookie already has an authenticated user
  useEffect(() => {
    authApi.me()
      .then(r => setUser(r.user))
      .catch(() => setUser(null))
      .finally(() => setLoading(false));
  }, []);

  const loadPayments = useCallback(async () => {
    setPaymentsLoading(true);
    try {
      const res = await paymongoApi.listPayments(10);
      setPayments(res.data);
    } catch { /* PayMongo keys not configured yet */ }
    finally { setPaymentsLoading(false); }
  }, []);

  useEffect(() => { if (user) loadPayments(); }, [user, loadPayments]);

  // ── Auth handlers ─────────────────────────────────────────────────────
  const handleAuth = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthLoading(true); setAuthError("");
    try {
      const res = authMode === "login"
        ? await authApi.login(authForm.email, authForm.password)
        : await authApi.register(authForm.name, authForm.email, authForm.password, authForm.password_confirmation);
      setUser(res.user);
    } catch (err: unknown) {
      setAuthError(err instanceof Error ? err.message : "Authentication failed.");
    } finally { setAuthLoading(false); }
  };

  const handleLogout = async () => {
    await authApi.logout().catch(() => {});
    setUser(null); setPayments([]);
  };

  // ── Checkout handler ──────────────────────────────────────────────────
  const handleCheckout = async (e: React.FormEvent) => {
    e.preventDefault();
    setCheckoutLoading(true); setCheckoutError("");
    try {
      const parsedItems = items.map(i => ({
        name: i.name, amount: parseInt(i.amount), quantity: parseInt(i.quantity),
      }));
      const res = await paymongoApi.checkout(parsedItems, description);
      window.open(res.checkout_url, "_blank");
    } catch (err: unknown) {
      setCheckoutError(err instanceof Error ? err.message : "Checkout failed.");
    } finally { setCheckoutLoading(false); }
  };

  const addItem    = () => setItems(p => [...p, { name: "", amount: "10000", quantity: "1" }]);
  const removeItem = (i: number) => setItems(p => p.filter((_, idx) => idx !== i));
  const updateItem = (i: number, field: string, val: string) =>
    setItems(p => p.map((item, idx) => idx === i ? { ...item, [field]: val } : item));

  // ── Loading state ─────────────────────────────────────────────────────
  if (loading) return (
    <div style={{ display: "flex", alignItems: "center", justifyContent: "center", height: "60vh" }}>
      <div className="spinner" style={{ width: 32, height: 32, borderWidth: 3 }} />
    </div>
  );

  // ── Render: Not logged in ─────────────────────────────────────────────
  if (!user) return (
    <div style={{ maxWidth: 420, margin: "0 auto", paddingTop: 40 }}>
      <div className="card">
        <h2>
          {authMode === "login" ? "Sign In" : "Create Account"}
          <span className="text-muted text-sm" style={{ fontWeight: 400, marginLeft: 8 }}>
            — secure session
          </span>
        </h2>

        {authError && <div className="alert alert-error">{authError}</div>}

        <form onSubmit={handleAuth}>
          {authMode === "register" && (
            <div className="mb-4">
              <label>Name</label>
              <input type="text" placeholder="Jerald Jose" value={authForm.name}
                onChange={e => setAuthForm(p => ({ ...p, name: e.target.value }))} required />
            </div>
          )}
          <div className="mb-4">
            <label>Email</label>
            <input type="email" placeholder="you@example.com" value={authForm.email}
              onChange={e => setAuthForm(p => ({ ...p, email: e.target.value }))} required />
          </div>
          <div className="mb-4">
            <label>Password</label>
            <input type="password" placeholder="••••••••" value={authForm.password}
              onChange={e => setAuthForm(p => ({ ...p, password: e.target.value }))} required />
          </div>
          {authMode === "register" && (
            <div className="mb-4">
              <label>Confirm Password</label>
              <input type="password" placeholder="••••••••" value={authForm.password_confirmation}
                onChange={e => setAuthForm(p => ({ ...p, password_confirmation: e.target.value }))} required />
            </div>
          )}
          <button type="submit" className="btn btn-primary w-full" disabled={authLoading}>
            {authLoading ? <span className="spinner" /> : authMode === "login" ? "Sign In" : "Register"}
          </button>
        </form>

        <div className="mt-4" style={{ textAlign: "center" }}>
          <button className="btn btn-outline btn-sm"
            onClick={() => { setAuthMode(p => p === "login" ? "register" : "login"); setAuthError(""); }}>
            {authMode === "login" ? "No account? Register" : "Have account? Sign in"}
          </button>
        </div>

        <div className="mt-4" style={{ padding: "10px 14px", background: "var(--surface2)", borderRadius: 8 }}>
          <p className="text-xs text-muted">
            Session secured with <strong>HttpOnly</strong> + <strong>SameSite=Lax</strong> cookies.
            No tokens stored in browser storage.
          </p>
        </div>
      </div>
    </div>
  );

  // ── Render: Logged in ─────────────────────────────────────────────────
  return (
    <div>
      {/* User bar */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1>PayMongo Playground</h1>
          <p className="text-muted text-sm mt-2">
            Signed in as <strong>{user.name}</strong> ({user.email})
            &nbsp;·&nbsp;
            <span className="badge badge-success" style={{ fontSize: 10 }}>Session Active</span>
          </p>
        </div>
        <button className="btn btn-danger btn-sm" onClick={handleLogout}>Sign Out</button>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 24, alignItems: "start" }}>

        {/* ── Checkout Form ── */}
        <div className="card">
          <h2>Create Checkout Session</h2>
          {checkoutError && <div className="alert alert-error">{checkoutError}</div>}

          <form onSubmit={handleCheckout}>
            <div className="mb-4">
              <label>Description</label>
              <input type="text" value={description}
                onChange={e => setDescription(e.target.value)} placeholder="Order description" />
            </div>

            <label style={{ marginBottom: 12, display: "block" }}>Line Items</label>
            {items.map((item, i) => (
              <div key={i} className="card" style={{ padding: 16, marginBottom: 12, background: "var(--surface2)" }}>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm font-mono text-muted">Item #{i + 1}</span>
                  {items.length > 1 &&
                    <button type="button" className="btn btn-danger btn-sm"
                      onClick={() => removeItem(i)}>x</button>}
                </div>
                <div className="mb-2">
                  <label>Name</label>
                  <input type="text" value={item.name} placeholder="Product name"
                    onChange={e => updateItem(i, "name", e.target.value)} required />
                </div>
                <div className="grid-2">
                  <div>
                    <label>Amount (centavos)</label>
                    <input type="number" min="2000" value={item.amount}
                      onChange={e => updateItem(i, "amount", e.target.value)} required />
                    <p className="text-xs text-muted mt-2">= {pesos(parseInt(item.amount || "0"))}</p>
                  </div>
                  <div>
                    <label>Quantity</label>
                    <input type="number" min="1" value={item.quantity}
                      onChange={e => updateItem(i, "quantity", e.target.value)} required />
                  </div>
                </div>
              </div>
            ))}

            <button type="button" className="btn btn-outline btn-sm mb-4 w-full" onClick={addItem}>
              + Add Item
            </button>

            <button type="submit" className="btn btn-primary w-full" disabled={checkoutLoading}>
              {checkoutLoading ? <><span className="spinner" /> Opening PayMongo...</> : "Pay with PayMongo ->"}
            </button>
          </form>

          <p className="text-xs text-muted mt-4">
            Opens PayMongo hosted checkout. Accepts GCash, Maya, QR Ph, Card, BillEase.
          </p>
        </div>

        {/* ── Payments List ── */}
        <div className="card">
          <div className="flex items-center justify-between mb-4">
            <h2 style={{ marginBottom: 0 }}>Recent Payments</h2>
            <button className="btn btn-outline btn-sm" onClick={() => loadPayments()}>
              {paymentsLoading ? <span className="spinner" /> : "Refresh"}
            </button>
          </div>

          {payments.length === 0 ? (
            <p className="text-muted text-sm">
              {paymentsLoading ? "Loading..." : "No payments yet. Create a checkout session above."}
            </p>
          ) : (
            <table>
              <thead>
                <tr>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Status</th>
                  <th>Paid At</th>
                </tr>
              </thead>
              <tbody>
                {payments.map(p => (
                  <tr key={p.id}>
                    <td><strong>{pesos(p.amount)}</strong></td>
                    <td className="text-muted">{p.payment_method ?? "—"}</td>
                    <td><span className={badgeClass(p.status)}>{p.status}</span></td>
                    <td className="text-xs text-muted">
                      {p.paid_at ? new Date(p.paid_at).toLocaleDateString("en-PH") : "—"}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      {/* Session Security Info */}
      <div className="card mt-6" style={{ background: "var(--surface2)" }}>
        <h3 className="mb-2">Session Security</h3>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 12, marginTop: 12 }}>
          {[
            { label: "HttpOnly Cookie",   desc: "JS cannot read session cookie" },
            { label: "SameSite=Lax",      desc: "Blocks cross-site CSRF requests" },
            { label: "Session Regeneration", desc: "New session ID issued on login" },
            { label: "UA + IP Binding",   desc: "Session locked to device fingerprint" },
          ].map(item => (
            <div key={item.label} style={{ padding: "12px 14px", background: "var(--bg)", borderRadius: 8, border: "1px solid var(--border)" }}>
              <p className="text-xs" style={{ color: "var(--success)", fontWeight: 600, marginBottom: 4 }}>+ {item.label}</p>
              <p className="text-xs text-muted">{item.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
