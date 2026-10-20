"use client";
import { useSearchParams } from "next/navigation";
import { Suspense } from "react";

function SuccessContent() {
  const params = useSearchParams();
  const sessionId = params.get("session_id");

  return (
    <div style={{ maxWidth: 480, margin: "60px auto", textAlign: "center" }}>
      <div className="card">
        <div style={{ fontSize: 56, marginBottom: 16 }}>✅</div>
        <h1 style={{ color: "var(--success)", marginBottom: 8 }}>Payment Successful!</h1>
        <p className="text-muted mt-4">Your PayMongo checkout session was completed successfully.</p>
        {sessionId && (
          <div style={{ marginTop: 20, padding: "12px 16px", background: "var(--surface2)", borderRadius: 8 }}>
            <p className="text-xs text-muted mb-1">Session ID</p>
            <code className="font-mono text-xs" style={{ color: "var(--accent)", wordBreak: "break-all" }}>
              {sessionId}
            </code>
          </div>
        )}
        <a href="/" className="btn btn-primary mt-6" style={{ display: "inline-flex" }}>
          ← Back to Playground
        </a>
      </div>
    </div>
  );
}

export default function SuccessPage() {
  return <Suspense><SuccessContent /></Suspense>;
}
