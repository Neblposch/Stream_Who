const CLIENT_ID = "ce0a01ea1d7845c1841c4ce345514573";
const REDIRECT_URI = "http://127.0.0.1:3000/logged-in";  // <-- PUT YOURS HERE
const SCOPES = "user-read-email playlist-read-private";

// UI elements
const loginBtn = document.getElementById("loginBtn");
const output = document.getElementById("output");


/* --------------------------
   PKCE HELPER FUNCTIONS
-------------------------- */

async function generateCodeVerifier(length = 64) {
    let text = "";
    let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for (let i = 0; i < length; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
}

async function generateCodeChallenge(codeVerifier) {
    const data = new TextEncoder().encode(codeVerifier);
    const digest = await crypto.subtle.digest("SHA-256", data);
    return btoa(String.fromCharCode(...new Uint8Array(digest)))
        .replace(/\+/g, "-")
        .replace(/\//g, "_")
        .replace(/=+$/, "");
}

/* --------------------------
   AUTH STEP 1: LOGIN
-------------------------- */

loginBtn.onclick = async () => {
    const codeVerifier = await generateCodeVerifier();
    const codeChallenge = await generateCodeChallenge(codeVerifier);

    // Save for use after redirect
    localStorage.setItem("code_verifier", codeVerifier);

    const url =
        "https://accounts.spotify.com/authorize" +
        `?client_id=${CLIENT_ID}` +
        `&response_type=code` +
        `&redirect_uri=${encodeURIComponent(REDIRECT_URI)}` +
        `&scope=${encodeURIComponent(SCOPES)}` +
        `&code_challenge_method=S256` +
        `&code_challenge=${codeChallenge}`;

    window.location = url;
};


/* --------------------------
   AUTH STEP 2: GET TOKEN
-------------------------- */

async function checkForAuthorizationCode() {
    const params = new URLSearchParams(window.location.search);
    const code = params.get("code");

    if (!code) return; // not returning from spotify redirect

    const codeVerifier = localStorage.getItem("code_verifier");

    const body = new URLSearchParams({
        grant_type: "authorization_code",
        code: code,
        redirect_uri: REDIRECT_URI,
        client_id: CLIENT_ID,
        code_verifier: codeVerifier,
    });

    const result = await fetch("https://accounts.spotify.com/api/token", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: body
    }).then(r => r.json());

    console.log(result);
    output.innerText = JSON.stringify(result, null, 2);

    localStorage.setItem("access_token", result.access_token);
}

/* --------------------------
   TEST API CALL
-------------------------- */

async function fetchUserProfile() {
    const token = localStorage.getItem("access_token");
    if (!token) return;

    const data = await fetch("https://api.spotify.com/v1/me", {
        headers: { "Authorization": "Bearer " + token }
    }).then(r => r.json());

    output.innerText = JSON.stringify(data, null, 2);
}

/* --------------------------
   RUN ON PAGE LOAD
-------------------------- */
checkForAuthorizationCode().then(fetchUserProfile);

// Attempt to use previously stored PKCE access token, fallback to provided static token.
const fallbackToken = 'BQAcWK8-PUM3tfM-Y_VhN8OuXw7qi4U17R4D9_XF7WfrX_zqK9wT2DfCN8lc1I8uMirv-lrSzKsaIbjuPhGN0FX_5wGGIOzK0EiYlOPvzHIJiQKQQKHoY27874fFS2e-rBbrJkIa7LifRvRym0P4zorNFObPeQDCvXWy7u0ZbQhwez0Ww2PPY_rVOKe5GuR51Kwr0F84JKoPXJqeJQV5RSNf3vIGI7uhjVWYw_lT_j9Ok-3yaNV32cKP3B_MtVJipngXmZ2pq5GyALBziLSGv-48rqOkXTKmIhuPqoiMhabmu9P9TEdI28A0Plc_tGwrK46i';
const token = localStorage.getItem('access_token') || fallbackToken;

const listEl = document.getElementById('topTracks');
const rawEl = document.getElementById('rawOutput');

async function fetchWebApi(endpoint, method = 'GET', body) {
    const options = {
        method,
        headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }
    };
    if (method !== 'GET' && body) options.body = JSON.stringify(body);
    const res = await fetch(`https://api.spotify.com/${endpoint}`, options);
    if (!res.ok) {
        throw new Error(`Request failed ${res.status} ${res.statusText}`);
    }
    return res.json();
}

async function getTopTracks() {
    const data = await fetchWebApi('v1/me/top/tracks?time_range=long_term&limit=5');
    return data.items || [];
}

function renderTracks(tracks) {
    if (!tracks.length) {
        listEl.innerHTML = '<li>No tracks found.</li>';
        return;
    }
    listEl.innerHTML = tracks.map(t =>
        `<li><strong>${sanitize(t.name)}</strong> — ${t.artists.map(a => sanitize(a.name)).join(', ')}</li>`
    ).join('');
    rawEl.textContent = JSON.stringify(tracks, null, 2);
}

// Basic text sanitization
function sanitize(str) {
    return str.replace(/[&<>"']/g, c => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));
}

async function init() {
    try {
        if (!token) {
            listEl.innerHTML = '<li>No access token available.</li>';
            return;
        }
        const topTracks = await getTopTracks();
        renderTracks(topTracks);
        console.log(topTracks.map(t => `${t.name} by ${t.artists.map(a => a.name).join(', ')}`));
    } catch (e) {
        listEl.innerHTML = `<li>Error: ${sanitize(e.message)}</li>`;
        rawEl.textContent = '';
        console.error(e);
    }
}

init();
