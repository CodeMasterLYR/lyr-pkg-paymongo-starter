"use client";
export default function CancelPage() {
  return (
    <div style={{ maxWidth: 480, margin: "60px auto", textAlign: "center" }}>
      <div className="card">
        <div style={{ fontSize: 56, marginBottom: 16 }}>❌</div>
        <h1 style={{ color: "var(--danger)", marginBottom: 8 }}>Payment Cancelled</h1>
        <p className="text-muted mt-4">You cancelled the PayMongo checkout. No charges were made.</p>
        <a href="/" className="btn btn-primary mt-6" style={{ display: "inline-flex" }}>
          ← Try Again
        </a>
      </div>
    </div>
  );
}
