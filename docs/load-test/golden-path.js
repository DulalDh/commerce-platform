/**
 * k6 load test covering the platform's critical golden path: login, browse
 * marketplace search, add-to-cart + checkout, and create a service booking.
 * Written for Phase 5 hardening - not run in this environment (no k6
 * binary/staging target available here), intended for execution against a
 * seeded staging environment before launch and after any change to the hot
 * paths below.
 *
 * Usage:
 *   k6 run -e BASE_URL=https://staging.example.test/api/v1 \
 *          -e EMAIL=bob@beta.test -e PASSWORD=password123 \
 *          -e SERVICE_ID=<uuid> \
 *          docs/load-test/golden-path.js
 *
 * SERVICE_ID must point to a pre-seeded, bookable service on staging -
 * booking creation depends on real staff/availability data this script
 * doesn't set up itself.
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8123/api/v1';
const EMAIL = __ENV.EMAIL || 'bob@beta.test';
const PASSWORD = __ENV.PASSWORD || 'password123';
const SERVICE_ID = __ENV.SERVICE_ID || '';

export const errorRate = new Rate('errors');
export const checkoutDuration = new Trend('checkout_duration', true);
export const searchDuration = new Trend('search_duration', true);

export const options = {
    scenarios: {
        golden_path: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '30s', target: 10 },
                { duration: '2m', target: 50 },
                { duration: '1m', target: 50 },
                { duration: '30s', target: 0 },
            ],
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<800', 'p(99)<2000'],
        errors: ['rate<0.01'],
        checkout_duration: ['p(95)<1500'],
    },
};

function authHeaders(token) {
    return { headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' } };
}

function login() {
    const res = http.post(
        `${BASE_URL}/auth/login`,
        JSON.stringify({ email: EMAIL, password: PASSWORD }),
        { headers: { 'Content-Type': 'application/json' } }
    );

    const ok = check(res, { 'login: 200': (r) => r.status === 200 });
    errorRate.add(!ok);

    return ok ? res.json('data.token') : null;
}

function browseAndSearch(token) {
    group('browse + search', () => {
        const searchRes = http.get(`${BASE_URL}/search?q=widget`, authHeaders(token));
        searchDuration.add(searchRes.timings.duration);
        errorRate.add(!check(searchRes, { 'search: 200': (r) => r.status === 200 }));

        const productsRes = http.get(`${BASE_URL}/products`, authHeaders(token));
        errorRate.add(!check(productsRes, { 'products: 200': (r) => r.status === 200 }));
    });
}

function checkoutFlow(token) {
    group('cart + checkout', () => {
        const productsRes = http.get(`${BASE_URL}/products`, authHeaders(token));
        const products = productsRes.json('data') || [];
        if (products.length === 0) return;

        const productId = products[0].id;

        const addRes = http.post(
            `${BASE_URL}/cart`,
            JSON.stringify({ product_id: productId, quantity: 1 }),
            authHeaders(token)
        );
        errorRate.add(!check(addRes, { 'add to cart: 2xx': (r) => r.status >= 200 && r.status < 300 }));

        const start = Date.now();
        const checkoutRes = http.post(
            `${BASE_URL}/checkout`,
            JSON.stringify({
                shipping_address: { line1: 'Load Test St', city: 'Testville', country: 'Testland' },
            }),
            authHeaders(token)
        );
        checkoutDuration.add(Date.now() - start);
        errorRate.add(!check(checkoutRes, { 'checkout: 2xx or expected 4xx': (r) => r.status < 500 }));
    });
}

function bookingFlow(token) {
    if (!SERVICE_ID) return;

    group('booking', () => {
        const date = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().slice(0, 10);

        const res = http.post(
            `${BASE_URL}/bookings`,
            JSON.stringify({
                service_id: SERVICE_ID,
                scheduled_date: date,
                scheduled_slot_start: '10:00',
                scheduled_slot_end: '11:00',
                address: { line1: 'Load Test St', city: 'Testville' },
            }),
            authHeaders(token)
        );
        errorRate.add(!check(res, { 'booking: 2xx or expected 4xx': (r) => r.status < 500 }));
    });
}

export default function () {
    const token = login();
    if (!token) {
        sleep(1);
        return;
    }

    browseAndSearch(token);
    sleep(1);
    checkoutFlow(token);
    sleep(1);
    bookingFlow(token);
    sleep(1);
}
