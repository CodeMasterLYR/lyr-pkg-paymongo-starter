/**
 * Session-based API client for LYR PayMongo Starter.
 *
 * Uses Sanctum SPA authentication:
 * - All requests send credentials (session cookie) via `credentials: "include"`
 * - CSRF token is fetched from GET /sanctum/csrf-cookie before mutations
 * - The XSRF-TOKEN cookie value is read and sent as X-XSRF-TOKEN header
 * - No tokens are stored in localStorage (prevents XSS token theft)
 */

const BASE_URL    = process.env.NEXT_PUBLIC_API_URL    ?? 'http://localhost:8000/api';
const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL ?? 'http://localhost:8000';

// -----------------------------------------------------------------------
// Types
// -----------------------------------------------------------------------
export interface User {
  id: number;
  name: string;
  email: string;
}

export interface CheckoutItem {
  name: string;
  amount: number;   // centavos — PHP 100.00 = 10000
  quantity: number;
}

export interface CheckoutResponse {
  checkout_url: string;
  session_id: string;
  expires_at: string | null;
}

export interface Payment {
  id: string;
  amount: number;
  currency: string;
  status: string;
  description: string | null;
  payment_method: string | null;
  paid_at: string | null;
}

// -----------------------------------------------------------------------
// CSRF helper — fetches /sanctum/csrf-cookie and reads XSRF-TOKEN cookie
// -----------------------------------------------------------------------
async function fetchCsrfCookie(): Promise<void> {
  await fetch(`${BACKEND_URL}/sanctum/csrf-cookie`, {
    method: 'GET',
    credentials: 'include',
  });
}

function getXsrfToken(): string {
  if (typeof document === 'undefined') return '';
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : '';
}

// -----------------------------------------------------------------------
// Core HTTP client
// -----------------------------------------------------------------------
async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const method = (options.method ?? 'GET').toUpperCase();
  const isMutation = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

  // Always refresh CSRF token before any state-changing request
  if (isMutation) await fetchCsrfCookie();

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
    ...(options.headers as Record<string, string>),
  };

  // Attach XSRF token for Sanctum CSRF verification
  if (isMutation) {
    const xsrf = getXsrfToken();
    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
  }

  const res = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers,
    credentials: 'include',   // Always send session cookie
  });

  // 204 No Content — no body to parse
  if (res.status === 204) return {} as T;

  const data = await res.json();

  if (!res.ok) {
    const message = data?.errors
      ? Object.values(data.errors as Record<string, string[]>).flat().join(' ')
      : (data?.message ?? `API error ${res.status}`);
    throw new Error(message);
  }

  return data as T;
}

// -----------------------------------------------------------------------
// Auth API (session-based — no tokens)
// -----------------------------------------------------------------------
export const authApi = {
  register: (name: string, email: string, password: string, password_confirmation: string) =>
    request<{ message: string; user: User }>('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ name, email, password, password_confirmation }),
    }),

  login: (email: string, password: string) =>
    request<{ message: string; user: User }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    }),

  logout: () =>
    request<{ message: string }>('/auth/logout', { method: 'POST' }),

  me: () =>
    request<{ user: User }>('/auth/me'),
};

// -----------------------------------------------------------------------
// PayMongo API (session auth)
// -----------------------------------------------------------------------
export const paymongoApi = {
  checkout: (items: CheckoutItem[], description: string) =>
    request<CheckoutResponse>('/paymongo/checkout', {
      method: 'POST',
      body: JSON.stringify({ items, description }),
    }),

  getSession: (id: string) =>
    request<{ session_id: string; status: string; checkout_url: string }>(`/paymongo/checkout/${id}`),

  listPayments: (limit = 10) =>
    request<{ data: Payment[] }>(`/paymongo/payments?limit=${limit}`),
};
