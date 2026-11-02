@php($cartCount = count(session('domain_cart', [])))
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Scalable VPS hosting with dedicated resources, full root access, and simple, transparent pricing. Launch your server in minutes.">
    <title>VPS Hosting | dfnhosting</title>
    <style>
:root {
    --ink: #13233f;
    --muted: #66758f;
    --navy: #102247;
    --navy-2: #183567;
    --blue: #2563eb;
    --blue-dark: #1747b5;
    --cyan: #16b8c4;
    --lime: #b8e86a;
    --paper: #f7f9fc;
    --line: #dfe7f1;
    --white: #fff;
    --shadow: 0 18px 50px rgba(20, 43, 85, .1);
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
    margin: 0;
    background: var(--paper);
    color: var(--ink);
    font: 16px/1.55 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
a { color: inherit; text-decoration: none; }
button, input, select { font: inherit; }
button, a { -webkit-tap-highlight-color: transparent; }
.page-width { width: min(1160px, calc(100% - 48px)); margin-inline: auto; }

/* ---------- Header ---------- */
.site-header {
    position: sticky;
    top: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: none;
    min-height: 76px;
    padding: 0 max(24px, calc((100% - 1160px) / 2));
    border-bottom: 1px solid rgba(255, 255, 255, .12);
    background: #0b1533;
    color: #dce7fb;
}
.logo { color: var(--white); font-size: 1.25rem; font-weight: 850; letter-spacing: -.055em; }
.logo::after { display: inline-block; width: 7px; height: 7px; margin: 0 0 2px 4px; border-radius: 50%; background: var(--lime); content: ""; }
.main-nav { display: flex; align-items: center; gap: 30px; font-size: .9rem; }
.main-nav > a, .hosting-menu summary { display: block; padding: 9px 0; color: #dce7fb; cursor: pointer; font-weight: 650; transition: color .2s ease; }
.main-nav > a:hover, .hosting-menu summary:hover, .hosting-menu[open] summary { color: var(--lime); }
.hosting-menu { position: relative; }
.hosting-menu summary { list-style: none; }
.hosting-menu summary::-webkit-details-marker { display: none; }
.hosting-menu summary::after { margin-left: 7px; color: #91a6cd; content: ""; font-size: .8rem; }
.hosting-menu-list {
    position: absolute;
    z-index: 5;
    top: calc(100% + 11px);
    right: 0;
    display: grid;
    min-width: 190px;
    gap: 3px;
    padding: 8px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--white);
    box-shadow: var(--shadow);
}
.hosting-menu-list a { padding: 10px 12px; border-radius: 8px; color: var(--ink); font-weight: 650; }
.hosting-menu-list a:hover { background: #edf4ff; color: var(--blue); }

/* ---------- Page banner ---------- */
.page-banner {
    position: relative;
    overflow: hidden;
    padding: 64px 0 58px;
    background: var(--navy);
    color: var(--white);
}
.page-banner::after {
    position: absolute;
    right: 7%;
    bottom: -150px;
    width: 330px;
    height: 330px;
    border: 1px solid rgba(255, 255, 255, .12);
    border-radius: 50%;
    box-shadow: 0 0 0 35px rgba(255, 255, 255, .035), 0 0 0 70px rgba(255, 255, 255, .025);
    content: "";
}
.page-banner .page-width { position: relative; z-index: 1; }
.page-banner h1 { margin: 0 0 18px; font-size: clamp(2.7rem, 6vw, 4.5rem); letter-spacing: -.075em; line-height: .98; }
.banner-kicker, .eyebrow { margin: 0 0 13px; color: var(--lime); font-size: .72rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
.breadcrumb { display: flex; gap: 10px; color: #aec0df; font-size: .9rem; }
.breadcrumb strong { color: var(--white); font-weight: 650; }
.breadcrumb a:hover { color: var(--lime); }

/* ---------- Pricing / plans ---------- */
.pricing { padding: 76px 0 90px; }
.intro-block { max-width: 780px; margin: 0 auto 38px; text-align: center; }
.intro-block .eyebrow { color: var(--blue); }
.intro-block h2 { margin: 0 0 15px; color: var(--ink); font-size: clamp(2rem, 4vw, 3.35rem); letter-spacing: -.07em; line-height: 1.05; }
.intro-block p:last-child { margin: 0; color: var(--muted); font-size: 1.05rem; }

.billing-tabs {
    display: flex;
    width: fit-content;
    max-width: 100%;
    margin: 0 auto;
    padding: 5px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--white);
    box-shadow: 0 10px 28px rgba(25, 52, 96, .08);
}
.billing-tab {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 112px;
    padding: 12px 17px;
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    font-size: .9rem;
    font-weight: 750;
}
.billing-tab::before { width: 7px; height: 7px; border: 1.5px solid #a9b9d0; border-radius: 50%; content: ""; }
.billing-tab:hover { color: var(--blue); background: #f1f6ff; }
.billing-tab.is-active { background: var(--navy); color: var(--white); box-shadow: 0 7px 15px rgba(16, 34, 71, .2); }
.billing-tab.is-active::before { border-color: var(--lime); background: var(--lime); }
.billing-tab:focus-visible, button:focus-visible, a:focus-visible { outline: 3px solid rgba(37, 99, 235, .35); outline-offset: 3px; }
.billing-note { min-height: 25px; margin: 13px 0 42px; color: var(--blue); text-align: center; font-size: .9rem; font-weight: 750; }

.plans { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; align-items: stretch; margin-top: 0; }
.plans .plan-card:last-child { grid-column: 2 / 4; justify-self: center; width: calc((100% - 18px) / 2); }
.plan-card, .plan-card-featured {
    position: relative;
    display: flex;
    min-height: 560px;
    flex-direction: column;
    padding: 44px 22px 24px;
    border: 1px solid #13233f;
    border-radius: 8px;
    background: var(--white);
    box-shadow: none;
    color: var(--ink);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.plan-card:hover, .plan-card-featured:hover { border-color: #101a2d; box-shadow: 0 16px 30px rgba(16, 26, 45, .14); transform: translateY(-4px); }
.plan-card-featured { border: 1px solid #13233f; background: var(--white); box-shadow: none; }

/* Simple plan labels keep the cards close to the reference layout. */
.plan-card h3.plan-circle {
    order: initial;
    width: auto;
    height: auto;
    margin: 0 0 14px;
    padding-bottom: 9px;
    border-bottom: 3px solid #6046d8;
    color: #172c50;
    font-size: 1.35rem;
    font-weight: 900;
    letter-spacing: -.025em;
    text-align: left;
    line-height: 1.2;
    text-transform: uppercase;
}
.plan-description { display: none; }
.price { display: flex; align-items: baseline; justify-content: flex-start; margin: 0 0 18px; padding-bottom: 0; border-bottom: 0; color: #07182f; font-size: 2.3rem; font-weight: 850; letter-spacing: -.07em; }
.currency { margin-right: 3px; color: #07182f; font-size: 1.65rem; }
.price small { margin-left: 6px; color: var(--muted); font-size: .82rem; font-weight: 550; letter-spacing: 0; }

.purchase-button {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 52px;
    margin: 0;
    padding: 11px 16px;
    border: 1px solid #101a2d;
    border-radius: 6px;
    background: var(--white);
    color: #101a2d;
    cursor: pointer;
    box-shadow: none;
    font-size: .82rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
}
.purchase-button.is-selected, .purchase-button:hover { background: #101a2d; color: var(--white); box-shadow: none; transform: translateY(-2px); }
.purchase-button .cart-icon, .purchase-button .arrow { display: none; }

.feature-groups { order: 3; max-height: 205px; min-width: 0; margin: 18px 0 0; padding: 4px 10px 4px 0; overflow-x: hidden; overflow-y: auto; border-top: 0; scrollbar-color: #8eafb6 transparent; scrollbar-width: thin; }
.feature-group + .feature-group { margin-top: 16px; }
.feature-group h4 { margin: 0 0 8px; color: var(--muted); font-size: .78rem; letter-spacing: .09em; text-transform: uppercase; }
.feature-group ul { display: grid; gap: 4px; margin: 0; padding: 0; list-style: none; }
.feature-group li { min-height: 34px; padding: 7px 0; color: var(--muted); font-size: .95rem; border-bottom: 0; }
.feature-group li::before { display: inline-grid; width: 18px; height: 18px; margin-right: 8px; place-items: center; border-radius: 50%; background: #e1f6f7; color: #0796a0; content: "\2713"; font-size: .72rem; font-weight: 800; }
.feature-group li.feature-unavailable { color: var(--muted); }
.feature-group li.feature-unavailable::before { background: #fff0f0; color: #e05252; content: "\00D7"; }
.feature-group li strong { color: #0b5f90; font-weight: 900; }
.features-toggle { order: 4; align-self: center; margin: 16px auto 0; padding: 8px 13px; border: 1px solid #a9bde0; border-radius: 8px; background: #f8fbff; color: var(--blue-dark); cursor: pointer; font-size: .76rem; font-weight: 750; }
.features-toggle:hover { border-color: var(--blue); background: #edf4ff; }

.popular-badge, .cheapest-badge {
    position: absolute;
    top: 14px;
    right: 14px;
    padding: 7px 11px;
    border-radius: 999px;
    color: var(--white);
    font-size: .62rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
    box-shadow: 0 6px 14px rgba(16, 26, 45, .2);
}
.popular-badge { background: linear-gradient(135deg, #6046d8, #37249c); }
.cheapest-badge { background: linear-gradient(135deg, #f0a52b, #d96a16); }

/* Reference-style pricing cards */
.pricing { background: var(--paper); }
.plans { gap: 38px; }
.plan-card, .plan-card-featured {
    min-height: 560px;
    padding: 58px 30px 28px;
    border: 1px solid #d8d8d4;
    border-radius: 0;
    background: #fff;
    box-shadow: none;
}
.plan-card:hover, .plan-card-featured:hover { border: 1px solid #111; background: #fff; box-shadow: 0 16px 30px rgba(20, 25, 25, .12); transform: translateY(-3px); }
.plan-card h3.plan-circle {
    margin: 0 0 14px;
    padding: 0;
    border: 0;
    color: #111;
    font-size: 1.9rem;
    font-weight: 500;
    letter-spacing: -.04em;
    text-transform: none;
}
.plan-description {
    order: 3;
    display: block;
    min-height: 56px;
    margin: 0 0 26px;
    color: #707070;
    font-size: 1.08rem;
    line-height: 1.5;
}
.plan-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
}
.price {
    position: relative;
    order: initial;
    justify-content: space-between;
    flex: 0 0 auto;
    margin: 0;
    color: #555;
    font-size: 1.8rem;
    font-weight: 500;
    letter-spacing: -.03em;
}
.price-unit { display: inline; margin-left: 6px; color: var(--muted); font-size: .85rem; font-weight: 400; }
.currency {
    position: absolute;
    top: -.17em;
    left: -.88em;
    margin: 0;
    color: #7a8c00;
    font-family: "Noto Sans Bengali", "Arial Unicode MS", "Segoe UI Symbol", sans-serif;
    font-size: 2rem;
    font-weight: 500;
    line-height: 1;
}
.price small { margin-left: 5px; color: var(--muted); font-size: .9rem; font-weight: 400; }
.purchase-button {
    order: 6;
    margin-top: 0;
    min-height: 70px;
    border: 0;
    border-radius: 0;
    background: #d6ff00;
    color: #111;
    font-size: 1rem;
    font-weight: 500;
    letter-spacing: 0;
    text-transform: none;
}
.purchase-button:hover, .purchase-button:active, .purchase-button.is-selected { background: #111; color: #fff; transform: none; }
.feature-groups {
    order: 4;
    flex: 1 1 auto;
    height: auto;
    min-height: 200px;
    max-height: 340px;
    margin: 0 0 10px;
    padding: 0 12px 4px 0;
    overflow-y: auto;
    scrollbar-color: #c9d5e5 transparent;
    scrollbar-gutter: stable;
    scrollbar-width: thin;
}
.feature-groups::-webkit-scrollbar { width: 6px; }
.feature-groups::-webkit-scrollbar-track { background: transparent; }
.feature-groups::-webkit-scrollbar-thumb { background: #c9d5e5; border-radius: 999px; }
.feature-groups::-webkit-scrollbar-button { display: none; }
.feature-group li:last-child { border-bottom: 0; }
.feature-group + .feature-group { margin-top: 20px; }
.feature-group h4 { margin: 0 0 14px; color: #555; font-size: .88rem; }
.feature-group ul { gap: 6px; min-width: 0; }
.feature-group li { position: relative; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 38px; padding: 6px 0 6px 34px; color: #222; font-size: .95rem; line-height: 1.45; letter-spacing: .02em; text-transform: uppercase; text-align: left; white-space: normal; }
.feature-group li::before { position: absolute; left: 0; top: 7px; display: inline-grid; place-items: center; width: 22px; height: 22px; margin: 0; border: 0; border-radius: 6px; background: #1747b5; color: #fff; font-family: "Segoe UI Symbol", "Noto Sans Symbols 2", sans-serif; font-size: .78rem; font-weight: 400; box-shadow: none; }
.feature-group li[data-icon="cpu"]::before { content: "\2699"; }
.feature-group li[data-icon="ram"]::before { content: "\25A6"; }
.feature-group li[data-icon="storage"]::before { content: "\25A4"; }
.feature-group li[data-icon="transfer"]::before { content: "\21C4"; }
.feature-group li[data-icon="internet"]::before { content: "\2194"; }
.feature-group li[data-icon="ix"]::before { content: "\21C6"; }
.feature-group li[data-icon="ip"]::before { content: "\25C9"; }
.feature-group li[data-icon="root"]::before { content: "\2318"; }
.feature-group li[data-icon="activation"]::before { content: "\26A1"; }
.feature-group li[data-icon="location"]::before { content: "\2316"; }
.feature-group li[data-icon="backup"]::before { content: "\21BB"; }
.feature-group li[data-icon="check"]::before { content: "\2713"; }
.feature-group li.feature-unavailable { color: #555; }
.feature-group li.feature-unavailable::before { border: 0; background: #fff0f0; color: #e05252; }
.feature-group li strong { color: #111; font-weight: 900; }
.features-toggle {
    order: 5;
    align-self: flex-start;
    margin: 10px 0 16px;
    padding: 10px 15px;
    border: 1px solid #c8d0dc;
    border-radius: 999px;
    background: #fff;
    color: #172c50;
    font-size: .88rem;
    font-weight: 750;
    letter-spacing: .01em;
    text-transform: none;
    box-shadow: 0 2px 5px rgba(20, 43, 85, .06);
    transition: border-color .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.features-toggle::after {
    display: inline-block;
    margin-left: 9px;
    content: "\2192";
    font-size: 1rem;
    line-height: 0;
    transition: transform .2s ease;
}
.features-toggle:hover {
    border-color: var(--blue);
    background: #f5f8ff;
    color: var(--blue-dark);
    box-shadow: 0 5px 14px rgba(37, 99, 235, .14);
    transform: translateY(-1px);
}
.features-toggle:hover::after { transform: translateX(3px); }
.features-toggle:focus-visible {
    outline: 3px solid rgba(37, 99, 235, .25);
    outline-offset: 3px;
}
.popular-badge, .cheapest-badge { top: -14px; right: 18px; left: auto; width: auto; max-width: fit-content; white-space: nowrap; z-index: 3; padding: 8px 14px; font-size: .68rem; letter-spacing: .08em; }
.popular-badge {
    background: #111;
    color: #d6ff00;
    border: 2px solid #d6ff00;
    box-shadow: 0 8px 20px rgba(214, 255, 0, .4), 0 8px 18px rgba(17, 17, 17, .3);
}
.cheapest-badge { background: #d6ff00; color: #111; border: 2px solid #fff; box-shadow: 0 8px 18px rgba(214, 255, 0, .45); }
.plan-card-featured {
    border: 2px solid #111 !important;
    box-shadow: 0 16px 30px rgba(17, 17, 17, .14) !important;
    transform: translateY(-4px);
}
.plan-card-featured:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 36px rgba(17, 17, 17, .18) !important;
}

/* Three-column plan row with the title and amount aligned like the reference. */
.plans { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 38px; }
.plans .plan-card:last-child { grid-column: auto; justify-self: stretch; width: auto; }
.plan-heading + .plan-description { order: 3; }

/* ---------- Feature dialog ---------- */
.features-dialog { width: min(560px, calc(100% - 32px)); max-height: calc(100vh - 40px); padding: 28px; border: 0; border-radius: 18px; background: var(--white); color: var(--ink); box-shadow: 0 24px 70px rgba(16, 34, 71, .3); }
.features-dialog::backdrop { background: rgba(16, 34, 71, .64); backdrop-filter: blur(4px); }
.dialog-header, .dialog-summary { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; }
.dialog-eyebrow { margin: 0 0 5px; color: var(--blue); font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.features-dialog h2 { margin: 0; color: var(--navy); font-size: 1.4rem; }
.dialog-close { display: grid; width: 31px; height: 31px; place-items: center; border: 0; border-radius: 50%; background: #fff0f0; color: #dc5656; cursor: pointer; font-size: 1.3rem; }
.dialog-summary { margin-top: 18px; padding: 16px 0; border-block: 1px solid var(--line); }
.dialog-summary p { margin: 0; color: var(--muted); }
.dialog-features { max-height: 480px; margin-top: 22px; overflow-y: auto; }
.dialog-category + .dialog-category { margin-top: 20px; }
.dialog-category h3 { margin: 0 0 7px; color: var(--navy); font-size: 1rem; }
.dialog-category ul { margin: 0; padding: 0; list-style: none; }
.dialog-category li { padding: 9px 0; border-bottom: 1px solid #e9eef5; color: var(--muted); }
.dialog-category li::before { margin-right: 9px; color: #0796a0; content: "\2713"; font-weight: 800; }
.dialog-category li.feature-unavailable { color: #aeb6c4; }
.dialog-category li.feature-unavailable::before { color: #dc5656; content: "\00D7"; }
.dialog-acl-note { margin: 24px 0 0; padding: 22px; border-top: 1px solid var(--line); border-radius: 12px; background: #f7f9fc; color: var(--muted); font-weight: 700; text-align: center; }

/* ---------- Why VPS / benefits ---------- */
.why-vps { padding: 18px 0 92px; }
.why-vps-head { max-width: 680px; margin: 0 auto 40px; text-align: center; }
.why-vps-head h2 { margin: 8px 0 12px; color: var(--navy); font-size: clamp(2rem, 4vw, 3rem); letter-spacing: -.07em; line-height: 1.05; }
.why-vps-head p { margin: 0; color: var(--muted); }
.benefits-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; }
.benefit-card { padding: 28px 24px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); box-shadow: 0 12px 30px rgba(20, 43, 85, .06); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.benefit-card:hover { border-color: #b7cbe8; box-shadow: var(--shadow); transform: translateY(-5px); }
.benefit-icon { display: grid; width: 48px; height: 48px; place-items: center; margin-bottom: 18px; border-radius: 13px; background: #e5efff; color: var(--blue); font-size: 1.2rem; font-weight: 850; }
.benefit-card h3 { margin: 0 0 9px; color: var(--navy); font-size: 1.12rem; letter-spacing: -.02em; }
.benefit-card p { margin: 0; color: var(--muted); font-size: .92rem; line-height: 1.6; }

/* ---------- Trust bar ---------- */
.trust-bar { padding: 0 0 90px; }
.trust-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 14px 46px; padding: 30px 24px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); box-shadow: var(--shadow); }
.trust-item { display: flex; align-items: center; gap: 11px; color: var(--ink); font-weight: 750; font-size: .98rem; }
.trust-item .dot { width: 9px; height: 9px; border-radius: 50%; background: var(--lime); box-shadow: 0 0 0 4px rgba(184, 232, 106, .25); }
.trust-item .dot.blue { background: var(--blue); box-shadow: 0 0 0 4px rgba(37, 99, 235, .18); }

/* ---------- Footer ---------- */
.site-footer { display: flex; flex-wrap: wrap; gap: 8px; justify-content: space-between; padding: 25px 0; color: var(--muted); font-size: .8rem; border-top: 1px solid var(--line); }
.site-footer a:hover { color: var(--blue); }

@media (max-width: 1080px) {
    .plans { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .plans .plan-card:last-child { grid-column: auto; justify-self: stretch; width: auto; }
    .benefits-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (min-width: 1081px) and (max-width: 1200px) {
    .plan-card, .plan-card-featured { padding-inline: 18px; }
    .price { font-size: 2.05rem; }
    .price small { font-size: .72rem; }
}
@media (max-width: 860px) {
    .plans { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .page-width { width: min(100% - 32px, 560px); }
    .site-header { min-height: 68px; padding-inline: 16px; }
    .main-nav { gap: 16px; }
    .page-banner { padding: 48px 0 42px; }
    .pricing, .why-vps, .trust-bar { padding-inline: 0; }
    .pricing { padding: 52px 0 65px; }
    .why-vps { padding: 18px 0 70px; }
    .trust-bar { padding: 0 0 70px; }
    .billing-tabs { width: 100%; display: grid; grid-template-columns: repeat(2, 1fr); }
    .billing-tab { min-width: 0; padding-inline: 8px; }
    .billing-tab::before { display: none; }
    .billing-note { margin-bottom: 30px; }
    .plans, .benefits-grid { grid-template-columns: 1fr; }
    .plan-card, .plan-card-featured { min-height: 0; }
    .trust-inner { gap: 12px 24px; }
}
    </style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/hosting-nav.css') }}?v={{ md5_file(public_path('css/hosting-nav.css')) }}">
</head>
<body>
<div class="hosting-nav">
@include('partials.site-navbar', ['navActive' => 'hosting'])
</div>

    <nav class="hosting-rail" aria-label="Switch hosting type">
        <a href="{{ route('hosting.shared') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg><strong>Shared</strong></a>
        <a href="{{ route('hosting.vps') }}" class="is-active" aria-current="page"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="2"/><rect x="3" y="13.5" width="18" height="6.5" rx="2"/><path d="M7 7.2h.01M7 16.7h.01"/></svg><strong>VPS</strong></a>
        <a href="{{ route('hosting.dedicated') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2"/><rect x="10.5" y="10.5" width="3" height="3"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/></svg><strong>Dedicated</strong></a>
    </nav>
    <section class="page-banner page-banner-compact page-banner-luxe">
        <div class="page-width">
            <p class="banner-kicker">Private virtual servers</p>
            <h1>VPS Hosting</h1>
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('landing') }}">Home</a>
                <span>/</span>
                <strong>VPS Hosting</strong>
            </nav>
        </div>
    </section>

    <main class="pricing page-width">
        <div class="hosting-bar hosting-bar-edge hosting-bar-half">
        <nav aria-label="Hosting type">
            <a href="{{ route('hosting.shared') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg><strong>Shared</strong></a>
            <a href="{{ route('hosting.vps') }}" aria-current="page"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="2"/><rect x="3" y="13.5" width="18" height="6.5" rx="2"/><path d="M7 7.2h.01M7 16.7h.01"/></svg><strong>VPS</strong></a>
            <a href="{{ route('hosting.dedicated') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2"/><rect x="10.5" y="10.5" width="3" height="3"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/></svg><strong>Dedicated</strong></a>
        </nav>
        </div>
        <style>
.page-banner-luxe{position:relative;overflow:hidden;padding:66px 0 60px;background:linear-gradient(115deg,#0b1533 0%,#102247 35%,#1b3b76 70%,#253da8 100%);background-size:280% 280%;animation:luxeBg 22s cubic-bezier(.37,0,.63,1) infinite alternate;will-change:background-position}
.page-banner-luxe::before{content:"";position:absolute;left:-70px;top:-90px;width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(56,130,255,.32),transparent 68%);filter:blur(2px);animation:luxeFloat 14s cubic-bezier(.37,0,.63,1) infinite alternate;pointer-events:none;will-change:transform;backface-visibility:hidden}
.page-banner-luxe::after{animation:luxeFloat2 16s cubic-bezier(.37,0,.63,1) infinite alternate;will-change:transform}
.page-banner-luxe .page-width{position:relative;z-index:1}
.page-banner-luxe .banner-kicker{display:inline-flex;align-items:center;gap:8px;margin:0 0 13px;padding:5px 12px;border:1px solid rgba(184,232,106,.35);border-radius:999px;background:rgba(184,232,106,.1)}
.page-banner-luxe .banner-kicker::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--lime)}
.page-banner-luxe h1{margin:0 0 16px;font-size:clamp(2.7rem,6vw,4.2rem);line-height:.98;letter-spacing:-.04em;text-shadow:0 2px 24px rgba(37,99,235,.35)}
.banner-sub{margin:0 0 12px;max-width:640px;color:#c5d2e6;font-size:.92rem;line-height:1.5}
.banner-sub strong{color:#fff;font-weight:800}
@keyframes luxeBg{from{background-position:0% 30%}to{background-position:100% 70%}}
@keyframes luxeFloat{from{transform:translate3d(0,0,0) scale(1)}to{transform:translate3d(34px,22px,0) scale(1.1)}}
@keyframes luxeFloat2{from{transform:translate3d(0,0,0)}to{transform:translate3d(0,-12px,0)}}
.pricing{padding-top:0}
.hosting-bar{margin-bottom:12px}
.billing-row{margin-bottom:4px}
.billing-note{margin:8px 0 20px}
@media(max-width:640px){.page-banner-luxe{padding:48px 0 42px}.banner-sub{font-size:.85rem}}
@media(prefers-reduced-motion:reduce){.page-banner-luxe,.page-banner-luxe::before,.page-banner-luxe::after,.page-banner-luxe .banner-kicker,.page-banner-luxe h1,.banner-sub,.page-banner-luxe .breadcrumb{animation:none}}
        </style>

        <style>
.hosting-bar{position:static;display:flex;justify-content:center;margin:0 0 18px;animation:hostingIn .45s ease both}
@keyframes hostingIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.hosting-bar nav{display:flex;gap:4px;padding:4px;border:1px solid var(--line);border-radius:999px;background:rgba(255,255,255,.92);backdrop-filter:blur(10px);box-shadow:0 10px 28px rgba(25,52,96,.1)}
.hosting-bar a{padding:9px 18px;border-radius:999px;color:var(--muted);font-size:.85rem;font-weight:750;transition:background .2s ease,color .2s ease,box-shadow .2s ease,transform .2s ease}
.hosting-bar a:hover{color:var(--blue);background:#f1f6ff;transform:translateY(-1px)}
.hosting-bar a:active{transform:scale(.97)}
.hosting-bar a[aria-current="page"]{background:var(--navy);color:#fff;box-shadow:0 7px 15px rgba(16,34,71,.2)}
.billing-row{display:flex;justify-content:center;margin:0 0 8px}
.billing-row .billing-tabs{margin:0 auto}
.coming-soon-panel{max-width:560px;margin:0 auto 20px;padding:40px 36px;border:1px solid var(--line);border-radius:18px;background:#fff;box-shadow:0 18px 50px rgba(20,43,85,.1);text-align:center}
.coming-soon-panel[hidden]{display:none}
.plans[hidden]{display:none !important}
.coming-soon-pill{display:inline-block;padding:6px 14px;border-radius:999px;background:var(--navy);color:#fff;font-size:.7rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;margin-bottom:16px}
.coming-soon-panel h3{margin:0 0 10px;color:var(--navy);font-size:1.6rem;letter-spacing:-.03em}
.coming-soon-panel p{margin:0 0 22px;color:var(--muted)}
.coming-soon-back{min-height:48px;padding:0 22px;border:0;border-radius:10px;background:var(--blue);color:#fff;font-weight:800;cursor:pointer;transition:background .2s ease,transform .2s ease}
.coming-soon-back:hover{background:var(--navy);transform:translateY(-1px)}
.plan-card.is-coming-soon{opacity:.72}
.plan-card.is-coming-soon .purchase-button{background:#e8edf4 !important;color:#667085 !important;box-shadow:none !important;pointer-events:none;cursor:not-allowed}
@media (max-width:640px){.hosting-bar nav{width:100%;justify-content:space-between}.hosting-bar a{flex:1;text-align:center;padding:9px 8px}}
        </style>
        <style>
.hosting-bar-half{position:static;display:flex;justify-content:flex-start;margin:0 0 24px;padding:0;background:transparent;border:0;animation:none;pointer-events:none}
.hosting-bar-half nav{display:flex;align-items:flex-start;gap:0;padding:0;border:0;background:transparent;box-shadow:none;backdrop-filter:none;pointer-events:auto}
.hosting-bar-half a{position:relative;display:inline-flex;flex-direction:column;align-items:center;gap:5px;width:120px;margin-left:-16px;padding:10px 8px 15px;overflow:hidden;text-align:center;background:linear-gradient(180deg,#ffffff 0%,#e9eefa 100%);border:1px solid #9fb3d3;border-radius:0 0 60px 60px / 0 0 32px 32px;box-shadow:0 4px 12px rgba(16,34,71,.08);transition:transform .25s ease,filter .25s ease,box-shadow .25s ease}
.hosting-bar-half a:first-child{margin-left:0}
.hosting-bar-half .di-svg{width:18px;height:18px;color:#54678a;transition:color .25s,transform .25s}
.hosting-bar-half strong{font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#2c3a55}
.hosting-bar-half a:hover{transform:translateY(2px);filter:brightness(1.05);box-shadow:0 8px 18px rgba(16,34,71,.2)}
.hosting-bar-half a:hover .di-svg{transform:translateY(-1px)}
.hosting-bar-half a[aria-current="page"]{z-index:3;width:132px;margin-top:0;padding:12px 10px 17px;background:linear-gradient(180deg,#2a4a8c 0%,#102247 58%,#0a142e 100%);border-color:#0a142e;box-shadow:0 10px 24px rgba(16,34,71,.55);color:#fff}
.hosting-bar-half a[aria-current="page"] .di-svg{color:#b8e86a}
.hosting-bar-half a[aria-current="page"] strong{color:#fff}
.hosting-bar-half a[aria-current="page"]::after{content:"";position:absolute;inset:0;background:linear-gradient(115deg,transparent 32%,rgba(255,255,255,.22) 48%,transparent 62%);transform:translateX(-130%);animation:halfSheen 4.5s ease infinite}
@keyframes halfSheen{0%,60%{transform:translateX(-130%)}90%,100%{transform:translateX(130%)}}
@media(max-width:640px){.hosting-bar-half{margin:0 0 18px}.hosting-bar-half nav{width:auto;padding:0}.hosting-bar-half a{flex:1;width:auto;min-width:0;margin-left:-10px;padding:8px 4px 12px;border-radius:0 0 44px 44px / 0 0 24px 24px}.hosting-bar-half a[aria-current="page"]{width:auto;margin:0}.hosting-bar-half .di-svg{width:15px;height:15px}.hosting-bar-half strong{font-size:.68rem}}
@media(prefers-reduced-motion:reduce){.hosting-bar-half a[aria-current="page"]::after{animation:none}}
        </style>
        <div class="billing-row">
        <div class="billing-tabs" role="tablist" aria-label="Billing period">
            <button class="billing-tab is-active" type="button" role="tab" aria-selected="true" data-period="monthly" data-soon="{{ $terms['monthly']['soon'] ? 'true' : 'false' }}">Monthly</button>
            <button class="billing-tab" type="button" role="tab" aria-selected="false" data-period="yearly" data-soon="{{ $terms['yearly']['soon'] ? 'true' : 'false' }}">Yearly</button>
            <button class="billing-tab" type="button" role="tab" aria-selected="false" data-period="twoYear" data-soon="{{ $terms['twoYear']['soon'] ? 'true' : 'false' }}">2 Years</button>
            <button class="billing-tab" type="button" role="tab" aria-selected="false" data-period="threeYear" data-soon="{{ $terms['threeYear']['soon'] ? 'true' : 'false' }}">3 Years</button>
        </div>
        </div>
        <p class="billing-note" id="billing-note">Flexible month-to-month billing</p>

        <div class="coming-soon-panel" hidden>
            <span class="coming-soon-pill">Coming soon</span>
            <h3>Long-term billing is on its way.</h3>
            <p>2-year and 3-year plans aren't available yet. Choose monthly or yearly to get started today.</p>
            <button class="coming-soon-back" type="button">View monthly plans</button>
        </div>
        <div class="plans" aria-label="VPS hosting plans">
        @foreach($plans as $plan)
        <article class="plan-card{{ $plan['badge'] === 'Most popular' ? ' plan-card-featured' : '' }}{{ $plan['terms']['monthly'] ? '' : ' is-coming-soon' }}" data-visible-monthly="{{ $plan['visibility']['monthly'] ? 'true' : 'false' }}" data-visible-yearly="{{ $plan['visibility']['yearly'] ? 'true' : 'false' }}" data-visible-twoyear="{{ $plan['visibility']['twoYear'] ? 'true' : 'false' }}" data-visible-threeyear="{{ $plan['visibility']['threeYear'] ? 'true' : 'false' }}">
          @if($plan['badge'] === 'Cheapest')
          <span class="cheapest-badge">Cheapest</span>
          @elseif($plan['badge'] === 'Most popular')
          <span class="popular-badge">Most popular</span>
          @endif
          <h3 class="plan-circle {{ $plan['class'] }}" data-monthly-name="{{ $plan['names']['monthly'] }}" data-yearly-name="{{ $plan['names']['yearly'] }}" data-two-year-name="{{ $plan['names']['twoYear'] }}" data-three-year-name="{{ $plan['names']['threeYear'] }}">{{ $plan['names']['monthly'] }}</h3>
          <p class="plan-description">{{ $plan['description'] }}</p>
          <div class="price"><span class="currency">&#2547;</span><span class="price-value" data-monthly="{{ $plan['prices']['monthly'] }}" data-yearly="{{ $plan['prices']['yearly'] }}" data-two-year="{{ $plan['prices']['twoYear'] }}" data-three-year="{{ $plan['prices']['threeYear'] }}" data-soon-monthly="{{ $plan['terms']['monthly'] ? 'false' : 'true' }}" data-soon-yearly="{{ $plan['terms']['yearly'] ? 'false' : 'true' }}" data-soon-twoyear="{{ $plan['terms']['twoYear'] ? 'false' : 'true' }}" data-soon-threeyear="{{ $plan['terms']['threeYear'] ? 'false' : 'true' }}">{{ $plan['terms']['monthly'] ? $plan['prices']['monthly'] : 'Coming soon' }}</span><small class="price-unit">/ month</small></div>
          <a class="purchase-button" href="{{ route('register') }}">
            <span data-monthly-label="Get {{ $plan['names']['monthly'] }}" data-yearly-label="Get {{ $plan['names']['yearly'] }}" data-two-year-label="Get {{ $plan['names']['twoYear'] }}" data-three-year-label="Get {{ $plan['names']['threeYear'] }}">Get {{ $plan['names']['monthly'] }}</span>
          </a>
          <ul>
            @foreach($plan['primary_features'] as $feature)
            <li class="{{ $feature['available'] ? '' : 'feature-unavailable' }}"><strong data-monthly="{{ $feature['monthly'] }}" data-yearly="{{ $feature['yearly'] }}">{{ $feature['monthly'] }}</strong> {{ $feature['name'] }}</li>
            @endforeach
          </ul>
          <button class="features-toggle" type="button" aria-haspopup="dialog" aria-controls="features-dialog">See more</button>
          <ul class="extra-features" hidden>
            @foreach($plan['extra_features'] as $feature)
            <li class="{{ $feature['available'] ? '' : 'feature-unavailable' }}" data-monthly="{{ $feature['monthly'] }}" data-yearly="{{ $feature['yearly'] }}" @if(!empty($feature['feature_key'])) data-feature="{{ $feature['feature_key'] }}" @endif>{{ $feature['monthly'] }}</li>
            @endforeach
          </ul>
          @foreach(['monthly', 'yearly', 'twoYear', 'threeYear'] as $hpTerm)
          <template class="feature-set" data-term="{{ $hpTerm }}">
            <ul>
            @foreach($plan['feature_sets'][$hpTerm]['primary'] as $feature)
              <li class="{{ $feature['available'] ? '' : 'feature-unavailable' }}"><strong>{{ $feature['lead'] }}</strong>{{ $feature['name'] !== '' ? ' ' . $feature['name'] : '' }}</li>
            @endforeach
            </ul>
            <ul class="extra-features" hidden>
            @foreach($plan['feature_sets'][$hpTerm]['extra'] as $feature)
              <li class="{{ $feature['available'] ? '' : 'feature-unavailable' }}" @if(!empty($feature['feature_key'])) data-feature="{{ $feature['feature_key'] }}" @endif>{{ $feature['text'] }}</li>
            @endforeach
            </ul>
          </template>
          @endforeach
        </article>
        @endforeach
    </div>
</main>

    <section class="why-vps page-width">
        <div class="why-vps-head">
            <p class="eyebrow">Why choose VPS</p>
            <p>Everything you need to run serious projects &mdash; and none of the busywork.</p>
        </div>
        <div class="benefits-grid">
            <div class="benefit-card">
                <span class="benefit-icon" aria-hidden="true">&#9881;</span>
                <h3>Root access</h3>
                <p>Full sudo/SSH control to install any stack, run containers, and tune the server your way.</p>
            </div>
            <div class="benefit-card">
                <span class="benefit-icon" aria-hidden="true">&#9883;</span>
                <h3>Dedicated resources</h3>
                <p>Guaranteed CPU, RAM, and NVMe that&rsquo;s yours alone &mdash; no noisy neighbors, no slowdowns.</p>
            </div>
            <div class="benefit-card">
                <span class="benefit-icon" aria-hidden="true">&#8635;</span>
                <h3>Scale in a click</h3>
                <p>Upgrade storage, bandwidth, or power instantly as your traffic and team grow.</p>
            </div>
            <div class="benefit-card">
                <span class="benefit-icon" aria-hidden="true">&#128274;</span>
                <h3>Hardened by default</h3>
                <p>Free SSL, automated snapshots, and DDoS protection keep your services online.</p>
            </div>
        </div>
    </section>

    <section class="trust-bar page-width">
        <div class="trust-inner">
            <span class="trust-item"><span class="dot blue"></span>99.9% uptime SLA</span>
            <span class="trust-item"><span class="dot"></span>Instant provisioning</span>
            <span class="trust-item"><span class="dot blue"></span>24/7 expert support</span>
            <span class="trust-item"><span class="dot"></span>30-day money-back</span>
        </div>
    </section>

    <footer class="hosting-footer">
        <div class="hosting-footer-inner">
            <a class="hf-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a>
            <span>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span>
            <span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span>
        </div>
    </footer>

    <dialog class="features-dialog" id="features-dialog">
        <div class="dialog-header">
            <div>
                <p class="dialog-eyebrow">Included with this plan</p>
                <h2 id="dialog-title">More features</h2>
            </div>
            <button class="dialog-close" type="button" aria-label="Close feature list">&#215;</button>
        </div>
        <div class="dialog-summary" id="dialog-summary"></div>
        <p class="dialog-acl-note">CMS control is included with this plan.</p>
    </dialog>

<script>
    const periodNotes = {
        monthly: 'Flexible month-to-month billing',
        yearly: 'Annual hosting package pricing',
        twoYear: 'Two-year plan pricing',
        threeYear: 'Three-year plan pricing'
    };
    const periodUnits = {
        monthly: '/ month',
        yearly: '/ year',
        twoYear: '/ 2 years',
        threeYear: '/ 3 years'
    };

    // data-soon-monthly / -yearly / -twoyear / -threeyear on .price-value
    // let every card flag its own billing terms independently.
    const soonKey = (period) =>
        'soon' + period.charAt(0).toUpperCase() + period.slice(1).toLowerCase();

    // Align each plan name and taka amount on one reference-style row.
    document.querySelectorAll('.plan-card').forEach((card) => {
        const heading = document.createElement('div');
        heading.className = 'plan-heading';
        const title = card.querySelector(':scope > h3.plan-circle');
        const price = card.querySelector(':scope > .price');
        card.insertBefore(heading, title);
        heading.append(title, price);
    });

    // Builds the grouped feature list for a card from its two source lists
    // (primary rows + extra rows). Used for the initial render and again
    // whenever the billing tab changes — the CMS stores separate feature
    // rows per billing term (template.feature-set on each card).
    const buildVpsFeatureGroups = (card, primaryItems, extraItems) => {
        let featureGroups = card.querySelector(':scope > .feature-groups');
        if (!featureGroups) {
            featureGroups = document.createElement('div');
            featureGroups.className = 'feature-groups';
            card.querySelector(':scope > ul:not(.extra-features)').replaceWith(featureGroups);
            const extraList = card.querySelector(':scope > .extra-features');
            if (extraList) extraList.remove();
        }
        featureGroups.innerHTML = '';
        [
            { title: 'Core resources', features: primaryItems },
            { title: 'Account & databases', features: extraItems.filter((feature) => !feature.classList.contains('feature-unavailable')) },
            { title: 'Advanced & security', features: extraItems.filter((feature) => feature.classList.contains('feature-unavailable')) }
        ].forEach(({ title, features }) => {
            if (!features.length) return;
            const group = document.createElement('section');
            group.className = 'feature-group';
            group.innerHTML = `<h4>${title}</h4>`;
            const list = document.createElement('ul');
            features.forEach((feature) => {
                // Keep quantities prominent for both primary and generated feature rows.
                if (!feature.querySelector('strong')) {
                    const match = feature.textContent.trim().match(/^([\d,]+)(\s+.*)$/);
                    if (match) {
                        feature.textContent = '';
                        const quantity = document.createElement('strong');
                        quantity.textContent = match[1];
                        feature.append(quantity, match[2]);
                    }
                }
                list.append(feature);
            });
            group.append(list);
            featureGroups.append(group);
        });
        tagVpsFeatureItems(featureGroups);
    };

    // Tag each feature with an icon key for the themed markers.
    const tagVpsFeatureItems = (root) => {
        root.querySelectorAll('.feature-group li').forEach((item) => {
            const text = item.textContent.toLowerCase();
            let icon = 'check';
            if (text.includes('cpu') || text.includes('core')) icon = 'cpu';
            else if (text.includes('ram')) icon = 'ram';
            else if (text.includes('nvme') || text.includes('storage') || text.includes('disk')) icon = 'storage';
            else if (text.includes('transactional')) icon = 'transfer';
            else if (text.includes('mbps') || text.includes('internet')) icon = 'internet';
            else if (text.includes(' ix')) icon = 'ix';
            else if (text.includes('dedicated ip') || text.includes(' ip')) icon = 'ip';
            else if (text.includes('root')) icon = 'root';
            else if (text.includes('activation') || text.includes('instant')) icon = 'activation';
            else if (text.includes('bangladesh') || text.includes('location') || text.includes('server')) icon = 'location';
            else if (text.includes('back-up') || text.includes('backup')) icon = 'backup';
            item.dataset.icon = icon;
            // Full text as tooltip for clamped rows.
            item.title = item.textContent.trim().replace(/\s+/g, ' ');
        });
    };

    // Build the grouped feature list for every plan card.
    document.querySelectorAll('.plan-card').forEach((card) => {
        const primaryList = card.querySelector(':scope > ul:not(.extra-features)');
        const extraList = card.querySelector(':scope > .extra-features');
        if (primaryList && extraList) {
            buildVpsFeatureGroups(card, [...primaryList.children], [...extraList.children]);
        }
    });

    // Wire up the billing tabs. These queries run AFTER the feature groups
    // are built, so the resource labels are present and update correctly.
    const purchaseButtons = document.querySelectorAll('.purchase-button');
    purchaseButtons.forEach((button) => {
        button.dataset.defaultLabel = button.querySelector('span').textContent;
        button.dataset.baseHref = button.href;
    });
    // Keep one clear call-to-action selected while allowing visitors to preview others.
    purchaseButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            if (button.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                return;
            }
            purchaseButtons.forEach((item) => item.classList.toggle('is-selected', item === button));
        });
    });

    const applyPeriod = (period) => {
        document.querySelectorAll('.plan-card').forEach((card) => {
            // Card name follows the billing term (per-term CMS names).
            const planTitle = card.querySelector('h3.plan-circle');
            const titleKey = period + 'Name';
            if (planTitle && planTitle.dataset[titleKey]) {
                planTitle.textContent = planTitle.dataset[titleKey];
            }

            const price = card.querySelector('.price-value');
            const purchaseButton = card.querySelector('.purchase-button');
            const label = purchaseButton.querySelector('span');
            const unit = card.querySelector('.price-unit');
            const soon = price.dataset[soonKey(period)] === 'true';
            const visible = card.dataset['visible' + period.charAt(0).toUpperCase() + period.slice(1).toLowerCase()] === 'true';

            card.hidden = !visible;
            card.classList.toggle('is-coming-soon', soon);
            price.textContent = soon ? 'Coming soon' : price.dataset[period];
            // The "Get {plan}" label carries every term's name (per-term CMS names).
            label.textContent = soon ? 'Coming soon' : (label.dataset[period + 'Label'] || purchaseButton.dataset.defaultLabel);
            purchaseButton.setAttribute('aria-disabled', soon ? 'true' : 'false');
            purchaseButton.tabIndex = soon ? -1 : 0;
            if (soon) {
                purchaseButton.href = '#';
                unit.textContent = '';
            } else {
                const url = new URL(purchaseButton.dataset.baseHref, window.location.href);
                url.searchParams.set('period', period);
                purchaseButton.href = url.toString();
                unit.textContent = periodUnits[period];
            }
            // Features: rebuild the groups from THIS term's template. The
            // CMS stores separate feature rows per billing term; a card
            // whose selected term is Coming soon just fades the card.
            if (!soon) {
                const template = card.querySelector(
                    `template.feature-set[data-term="${period}"]`,
                );
                if (template) {
                    const scope = template.content.cloneNode(true);
                    buildVpsFeatureGroups(
                        card,
                        [...scope.querySelector('ul:not(.extra-features)').children],
                        [...scope.querySelector('ul.extra-features').children]
                    );
                }
            }
        });
    };

    const currentTab = document.querySelector('.billing-tab.is-active') || document.querySelector('.billing-tab');
    if (currentTab) {
        applyPeriod(currentTab.dataset.period);
    }

    document.querySelectorAll('.billing-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const period = tab.dataset.period;
            document.querySelectorAll('.billing-tab').forEach((item) => {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            applyPeriod(period);
            // The tab is "Coming soon" only when NO visible card offers it.
            const tabSoon = tab.dataset.soon === 'true';
            document.querySelector('#billing-note').textContent = tabSoon ? 'Coming soon' : periodNotes[period];
        });
    });

    // Feature dialog.
    document.querySelectorAll('.features-toggle').forEach((toggle) => {
        toggle.textContent = 'See more';
        toggle.addEventListener('click', () => {
            const card = toggle.closest('.plan-card');
            const dialog = document.querySelector('#features-dialog');
            const title = card.querySelector('h3').textContent;
            const description = card.querySelector('.plan-description').textContent;
            const price = card.querySelector('.price').cloneNode(true);
            const summary = document.querySelector('#dialog-summary');
            document.querySelector('#dialog-title').textContent = `${title} plan features`;
            summary.innerHTML = `<p>${description}</p><div class="dialog-price"></div>`;
            summary.querySelector('.dialog-price').append(price);
            dialog.showModal();
        });
    });

    const featuresDialog = document.querySelector('#features-dialog');
    featuresDialog.querySelector('.dialog-close').addEventListener('click', () => featuresDialog.close());
    featuresDialog.addEventListener('click', (event) => {
        if (event.target === featuresDialog) {
            featuresDialog.close();
        }
    });
</script>
<script>
(function () {
    var plans = document.querySelector('.plans');
    var panel = document.querySelector('.coming-soon-panel');
    if (!plans || !panel) return;
    function sync() {
        var active = document.querySelector('.billing-tab.is-active');
        var soon = active ? active.dataset.soon === 'true' : false;
        plans.hidden = soon;
        panel.hidden = !soon;
    }
    document.querySelectorAll('.billing-tab').forEach(function (tab) {
        tab.addEventListener('click', sync);
    });
    var back = panel.querySelector('.coming-soon-back');
    if (back) {
        back.addEventListener('click', function () {
            var monthly = document.querySelector('.billing-tab[data-period="monthly"]');
            if (monthly) monthly.click();
        });
    }
    sync();
})();
</script>
    @include('partials.chat')
<script>
document.addEventListener('click',function(e){var m=document.getElementById('accountMenu');if(m&&!m.contains(e.target)){m.classList.remove('open');}});
(function(){var rail=document.querySelector('.hosting-rail');var banner=document.querySelector('.page-banner');if(!rail||!banner)return;function sync(){var past=banner.getBoundingClientRect().bottom<120;rail.classList.toggle('show',past);}document.addEventListener('scroll',sync,{passive:true});window.addEventListener('resize',sync);sync();})();
</script>
  </body>
</html>
