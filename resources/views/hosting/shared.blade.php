<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Affordable shared hosting with reliable performance for growing websites.">
    <title>Shared Hosting | CloudTech</title>
    <style>
:root {
    --navy: #0f172a;
    --navy-deep: #0f172a;
    --blue: #2563eb;
    --blue-soft: #eff6ff;
    --ink: #0f172a;
    --muted: #94a3b8;
    --paper: #f8fafc;
    --line: #e5e7eb;
    --lime: #b8e36b;
    --gradient: linear-gradient(135deg, #2f73e8 0%, #5c3b80 100%);
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
    margin: 0;
    background: var(--paper);
    color: var(--ink);
    font-family: Arial, Helvetica, sans-serif;
    line-height: 1.5;
}
a { color: inherit; text-decoration: none; }
button { font: inherit; }
.page-width { width: min(1280px, calc(100% - 64px)); margin-inline: auto; }

.site-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: none;
    min-height: 78px;
    padding: 0 max(32px, calc((100% - 1280px) / 2));
    border-bottom: 1px solid #ffffff1c;
    background: var(--navy-deep);
}
.logo { color: #fff; font-size: 1.18rem; font-weight: 800; letter-spacing: -0.04em; }
.logo span { color: var(--blue); margin: 0 3px; }
.main-nav { display: flex; align-items: center; gap: 28px; color: #dce5f7; font-size: .9rem; }
.main-nav > a, .hosting-menu summary { display: block; padding: 8px 0; font-weight: 600; transition: color .2s ease; }
.main-nav > a:hover, .hosting-menu summary:hover { color: var(--blue); }
.main-nav a:hover, .hosting-menu summary:hover { color: var(--blue); }
.hosting-menu { position: relative; }
.hosting-menu summary { cursor: pointer; list-style: none; }
.hosting-menu[open] summary { color: var(--blue); }
.hosting-menu summary::-webkit-details-marker { display: none; }
.hosting-menu summary::after { display: inline-block; margin-left: 6px; content: "▾"; font-size: .7rem; }
.hosting-menu-list { position: absolute; z-index: 5; top: calc(100% + 14px); right: 0; display: grid; min-width: 175px; gap: 4px; padding: 8px; border: 1px solid var(--line); border-radius: 12px; background: #fff; box-shadow: 0 14px 30px #18243d1c; }
.hosting-menu-list a { padding: 9px 10px; border-radius: 7px; color: var(--navy); font-weight: 600; white-space: nowrap; }
.hosting-menu-list a:hover { background: var(--blue-soft); }

.page-banner {
    padding: 58px 0 52px;
    background: var(--navy);
    color: #fff;
}
.banner-kicker, .eyebrow {
    margin: 0 0 13px;
    color: var(--lime);
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
}
.page-banner h1 {
    margin: 0 0 25px;
    font-size: clamp(2.5rem, 5vw, 4.3rem);
    letter-spacing: -.06em;
    line-height: 1;
}
.breadcrumb { display: flex; gap: 9px; color: #9dadcf; font-size: .86rem; }
.breadcrumb a:hover { color: #fff; }
.breadcrumb strong { color: #fff; font-weight: 500; }

.pricing { padding: 74px 0 55px; }
.intro-block { max-width: 900px; text-align: center; margin: 0 auto 36px; }
.intro-block .eyebrow { color: var(--blue); }
.intro-block h2 { margin: 0 0 13px; font-size: clamp(2rem, 4vw, 3.2rem); line-height: 1.05; letter-spacing: -.06em; }
.intro-block p:last-child { margin: 0; color: var(--muted); }

.billing-tabs {
    position: relative;
    display: flex;
    width: fit-content;
    margin: 0 auto 0;
    padding: 6px;
    border: 1px solid #d7dfeb;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 12px 28px #18243d12, inset 0 1px #fff;
}
.billing-tab {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 116px;
    padding: 12px 17px;
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    font-size: .9rem;
    font-weight: 700;
    letter-spacing: .01em;
    transition: color .2s ease, background .2s ease, box-shadow .2s ease;
}
.billing-tab::before {
    width: 6px;
    height: 6px;
    border: 1.5px solid #aeb9ca;
    border-radius: 50%;
    content: "";
    transition: background .2s ease, border-color .2s ease, box-shadow .2s ease;
}
.billing-tab:hover { color: var(--navy); background: #f5f8fc; }
.billing-tab.is-active {
    background: var(--navy);
    color: #fff;
    box-shadow: 0 6px 14px #101d4330;
}
.billing-tab.is-active::before {
    border-color: var(--lime);
    background: var(--lime);
    box-shadow: 0 0 0 3px #b8e36b26;
}
.billing-tab:focus-visible {
    outline: 3px solid #2f73e855;
    outline-offset: 2px;
}
.billing-note { min-height: 24px; margin: 11px 0 34px; color: var(--blue); text-align: center; font-size: .9rem; font-weight: 700; }

.plans { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); column-gap: 22px; row-gap: 76px; margin-top: 65px; }
.plans .plan-card:last-child { grid-column: 2; position: relative; left: 50%; }
.plan-card {
    position: relative;
    display: flex;
    flex-direction: column;
    padding: 78px 24px 24px;
    border: 1px solid var(--line);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 12px 30px #18243d08;
    overflow: visible;
    transition: transform .2s ease, box-shadow .2s ease;
}
.shared-theme .plan-card { min-height: 720px; }
.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 18px 35px #18243d14;
}
.plan-card-featured { border-color: var(--line); padding: 78px 24px 24px; box-shadow: 0 12px 30px #18243d08; }
.plan-card h3.plan-circle {
    position: absolute;
    top: -58px;
    left: 50%;
    display: grid;
    width: 116px;
    height: 116px;
    margin: 0;
    place-items: center;
    border-radius: 50%;
    color: #fff;
    font-size: 1.05rem;
    font-weight: 500;
    letter-spacing: .01em;
    transform: translateX(-50%);
}
.plan-circle-basic { background: #1476ed; }
.plan-circle-standard { background: #075989; }
.plan-circle-advanced { background: #283b55; }
.plan-circle-premium { background: #252525; }
.plan-circle-enterprise { background: #5c3b80; }
.plan-description { min-height: 24px; margin: 0 0 13px; color: #657188; font-size: 1rem; font-weight: 500; line-height: 1.45; }
.price { display: flex; align-items: baseline; margin-bottom: 16px; color: var(--navy); font-size: 2.5rem; font-weight: 800; letter-spacing: -.06em; }
.currency { margin-right: 2px; color: var(--blue); font-size: 1.3rem; }
.price small { margin-left: 5px; color: var(--muted); font-size: .9rem; font-weight: 400; letter-spacing: 0; }
.plan-button { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 13px 16px; border: 1px solid var(--blue); border-radius: 10px; background: var(--blue); color: #fff; font-size: .88rem; font-weight: 700; letter-spacing: .01em; box-shadow: 0 8px 18px #2f73e82e; transition: background .2s ease, border-color .2s ease, color .2s ease, box-shadow .2s ease, transform .2s ease; }
.plan-button:hover { border-color: var(--navy); background: var(--navy); color: #fff; box-shadow: 0 12px 24px #101d4333; transform: translateY(-1px); }
.plan-button:active { box-shadow: 0 4px 10px #101d4324; transform: translateY(0); }
.plan-button:focus-visible { outline: 3px solid #2f73e855; outline-offset: 3px; }
.plan-button span { font-size: 1.2rem; transition: transform .2s ease; }
.plan-button:hover span { transform: translate(2px, -2px); }
.purchase-button {
    position: relative;
    display: grid;
    align-items: center;
    grid-template-columns: 25px 1fr 25px;
    gap: 8px;
    width: 100%;
    margin-top: 18px;
    min-height: 54px;
    padding: 10px;
    border: 1px solid #4545df;
    border-radius: 10px;
    background: linear-gradient(135deg, #5757f3 0%, #4747e5 100%);
    color: #fff;
    font-size: .94rem;
    font-weight: 800;
    letter-spacing: 0;
    white-space: nowrap;
    box-shadow: 0 6px 16px #4545df3d, inset 0 1px #8585ff80;
    transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease, filter .2s ease;
}
.purchase-button:hover {
    transform: translateY(-2px);
    border-color: #3434c8;
    filter: saturate(1.08);
    box-shadow: 0 11px 22px #4545df52, inset 0 1px #8d8dff;
}
.purchase-button:active {
    transform: translateY(0);
    box-shadow: 0 4px 10px #4545df38, inset 0 2px 4px #25258c40;
}
.purchase-button:focus-visible {
    outline: 3px solid #5757f366;
    outline-offset: 3px;
}
.purchase-button .cart-icon {
    position: absolute;
    left: 14px;
    width: 19px;
    height: 19px;
    flex-shrink: 0;
    transition: transform .2s ease;
}
.purchase-button > span {
    grid-column: 2;
    padding: 0;
    font-weight: 800;
    white-space: nowrap;
    text-align: center;
}
.purchase-button:hover .cart-icon {
    transform: translateY(-1px);
}
.purchase-button .arrow {
    position: static;
    grid-column: 3;
    display: grid;
    width: 25px;
    height: 25px;
    padding: 6px;
    border-radius: 7px;
    background: #ffffff2e;
    flex-shrink: 0;
    transition: transform .2s ease, background .2s ease;
}
.purchase-button .cart-icon {
    position: static;
    grid-column: 1;
}
.purchase-button:hover .arrow {
    background: #ffffff52;
    transform: translateX(3px);
}
@media (max-width: 480px) {
    .purchase-button {
        grid-template-columns: 24px 1fr;
    }
    .purchase-button > span {
        font-size: .88rem;
    }
    .purchase-button .arrow {
        display: none;
    }
}
.feature-groups {
    max-height: 365px;
    margin-top: 22px;
    padding: 20px 10px 8px 0;
    overflow-y: auto;
    border-top: 1px solid var(--line);
    scrollbar-color: #b9cbe3 transparent;
    scrollbar-width: thin;
}
.feature-group + .feature-group { margin-top: 22px; }
.feature-group h4 { margin: 0 0 10px; color: var(--navy); font-size: .82rem; letter-spacing: .06em; text-transform: uppercase; }
.feature-group ul { display: grid; gap: 12px; margin: 0; padding: 0; list-style: none; color: #657188; font-size: .95rem; font-weight: 400; line-height: 1.45; }
.feature-group li { min-height: 18px; }
.plan-card li::before { content: "\2713"; display: inline-grid; width: 18px; height: 18px; place-items: center; margin-right: 8px; border-radius: 6px; background: #4b4be8; color: #fff; font-size: .7rem; font-weight: 700; box-shadow: 0 3px 7px #4b4be82e; }
.shared-theme .plan-card li.feature-unavailable { color: #6f7b91; }
.shared-theme .plan-card li.feature-unavailable::before { content: "×"; background: #e8edf4; color: #6f7b91; font-size: .8rem; box-shadow: none; }
.plan-card li strong { color: var(--navy); font-weight: 700; }
.features-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
    gap: 9px;
    margin: 18px auto 0;
    padding: 9px 13px 9px 15px;
    border: 1px solid #d8e1ee;
    border-radius: 999px;
    background: #f8fafc;
    color: var(--navy);
    cursor: pointer;
    font-size: .88rem;
    font-weight: 700;
    letter-spacing: .01em;
    text-align: center;
    transition: border-color .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.features-toggle:hover {
    border-color: #b9cbe3;
    background: #eef4fb;
    color: var(--blue);
    box-shadow: 0 5px 12px #18243d12;
    transform: translateY(-1px);
}
.features-toggle:focus-visible { outline: 3px solid #2f73e855; outline-offset: 3px; }
.features-toggle::after {
    display: grid;
    width: 18px;
    height: 18px;
    place-items: center;
    border-radius: 50%;
    background: var(--blue-soft);
    color: var(--blue);
    content: "+";
    font-size: .9rem;
    font-weight: 500;
    line-height: 1;
}
.feature-groups::-webkit-scrollbar { width: 8px; }
.feature-groups::-webkit-scrollbar-thumb { border: 2px solid transparent; border-radius: 999px; background: #b9cbe3; background-clip: padding-box; }
.feature-groups::-webkit-scrollbar-track { background: transparent; }
.features-dialog {
    width: min(560px, calc(100% - 32px));
    max-height: min(720px, calc(100vh - 48px));
    padding: 28px;
    overflow: hidden;
    border: 0;
    border-radius: 18px;
    background: #fff;
    color: var(--ink);
    box-shadow: 0 24px 70px #101d4350;
}
.features-dialog::backdrop { background: #101d4380; backdrop-filter: blur(3px); }
.dialog-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; }
.dialog-eyebrow { margin: 0 0 5px; color: var(--blue); font-size: .68rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
.features-dialog h2 { margin: 0; color: var(--navy); font-size: 1.45rem; letter-spacing: -.04em; }
.dialog-close { display: grid; width: 30px; height: 30px; place-items: center; padding: 0; border: 0; border-radius: 50%; background: #fff0f0; color: #ef5350; cursor: pointer; font-size: 1.35rem; line-height: 1; }
.dialog-close:hover { background: #ef5350; color: #fff; }
.dialog-summary { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 18px; padding: 16px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
.dialog-summary p { margin: 0; color: var(--muted); font-size: .95rem; }
.dialog-price .price { margin: 0; font-size: 1.8rem; }
.dialog-price .price-unit { font-size: .8rem; }
.shared-theme + .features-dialog .dialog-acl-note { margin: 24px 0 0; padding: 22px; border-top: 1px solid var(--line); border-radius: 12px; background: #f7f9fc; color: var(--muted); font-size: 1rem; font-weight: 700; text-align: center; }
.dialog-features { display: block; max-height: 530px; margin: 24px 0 0; padding: 8px 8px 8px 0; overflow-y: auto; border-top: 1px solid var(--line); color: var(--muted); font-size: 1rem; }
.dialog-category + .dialog-category { margin-top: 22px; }
.dialog-category h3 { margin: 0; padding: 0 0 8px; color: var(--navy); font-size: 1.08rem; letter-spacing: -.01em; }
.dialog-category ul { margin: 0; padding: 0; list-style: none; }
.dialog-category li { display: flex; align-items: center; min-height: 44px; padding: 9px 0; border-bottom: 1px solid #e8edf4; font-weight: 400; }
.dialog-features li:not(.feature-unavailable) { color: #6f7b91; }
.dialog-features li strong { margin-right: 4px; color: #111827; font-weight: 700; }
.dialog-features li::before { content: "\2713"; display: inline-grid; width: 20px; height: 20px; flex: 0 0 20px; place-items: center; margin-right: 11px; border-radius: 6px; background: #4b4be8; color: #fff; font-size: .72rem; font-weight: 700; }
.dialog-features li.feature-unavailable::before { content: "\00D7"; background: #fff0f0; color: #ef5350; font-size: .95rem; }

.trust-strip { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 26px 0 45px; border-top: 1px solid var(--line); color: var(--muted); font-size: .82rem; }
.trust-strip span { color: var(--navy); font-weight: 700; }
.trust-strip div { display: flex; align-items: center; gap: 15px; }
.trust-strip b { color: var(--blue); }
.trust-strip i { width: 4px; height: 4px; border-radius: 50%; background: #b8c2d2; }
.site-footer { display: flex; justify-content: space-between; padding: 25px 0; color: var(--muted); font-size: .78rem; }
.home-page { min-height: calc(100vh - 150px); }
.home-hero { padding: 104px 0 118px; background: linear-gradient(135deg, #f7f9ff 0%, #eef3ff 52%, #fff 100%); }
.home-hero-inner { max-width: 820px; text-align: center; }
.home-hero h1 { max-width: 720px; margin: 14px auto 18px; color: var(--navy); font-size: clamp(2.7rem, 6vw, 5.2rem); letter-spacing: -.065em; line-height: .98; }
.home-lead { max-width: 590px; margin: 0 auto 30px; color: var(--muted); font-size: 1.1rem; line-height: 1.7; }
.home-primary-button { display: inline-flex; align-items: center; justify-content: center; min-height: 50px; padding: 0 24px; border-radius: 10px; background: var(--blue); color: #fff; font-weight: 800; box-shadow: 0 10px 22px #2f73e833; transition: transform .2s ease, box-shadow .2s ease, background .2s ease; }
.home-primary-button:hover { background: var(--navy); color: #fff; transform: translateY(-2px); box-shadow: 0 14px 26px #101d4330; }
.hosting-options { padding: 88px 0 100px; }
.home-section-heading { max-width: 620px; margin: 0 auto 38px; text-align: center; }
.home-section-heading h2 { margin: 9px 0 12px; color: var(--navy); font-size: clamp(2rem, 4vw, 3rem); letter-spacing: -.05em; }
.home-section-heading > p:last-child { margin: 0; color: var(--muted); font-size: 1rem; line-height: 1.6; }
.hosting-option-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; }
.hosting-option-card { display: flex; min-height: 300px; flex-direction: column; padding: 30px; border: 1px solid var(--line); border-radius: 18px; background: #fff; color: var(--ink); box-shadow: 0 12px 30px #18243d08; transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
.hosting-option-card:hover { border-color: #b9cbe3; color: var(--ink); transform: translateY(-5px); box-shadow: 0 18px 35px #18243d14; }
.hosting-option-card-featured { border-color: #b9b9f6; box-shadow: 0 14px 32px #4b4be81c; }
.hosting-option-icon { display: grid; width: 46px; height: 46px; place-items: center; margin-bottom: 22px; border-radius: 13px; background: var(--blue-soft); color: var(--blue); font-size: 1.15rem; font-weight: 800; }
.hosting-option-card h3 { margin: 0 0 11px; color: var(--navy); font-size: 1.45rem; letter-spacing: -.03em; }
.hosting-option-card p:not(.hosting-option-label) { margin: 0; color: var(--muted); line-height: 1.65; }
.hosting-option-label { margin: -41px 0 28px 60px; color: var(--blue); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.hosting-option-link { display: flex; justify-content: space-between; margin-top: auto; padding-top: 24px; color: var(--blue); font-size: .9rem; font-weight: 800; }
.hosting-option-link span { font-size: 1.15rem; transition: transform .2s ease; }
.hosting-option-card:hover .hosting-option-link span { transform: translateX(4px); }

@media (max-width: 720px) {
    .page-width { width: min(100% - 32px, 1280px); }
    .main-nav { gap: 18px; }
    .site-header { width: 100%; min-height: 68px; padding: 0 16px; }
    .page-banner {
        position: relative;
        overflow: hidden;
        padding: 48px 0 42px;
        background:
            radial-gradient(circle at 88% 10%, #31569b 0, transparent 34%),
            linear-gradient(145deg, var(--navy-deep), var(--navy));
    }
    .page-banner::after {
        position: absolute;
        right: -55px;
        bottom: -75px;
        width: 190px;
        height: 190px;
        border: 1px solid #ffffff1c;
        border-radius: 50%;
        box-shadow: 0 0 0 18px #ffffff08, 0 0 0 36px #ffffff05;
        content: "";
    }
    .page-banner .page-width { position: relative; z-index: 1; }
    .pricing { padding: 48px 0 32px; }
    .intro-block {
        margin-bottom: 28px;
        padding: 23px 20px 22px;
        border: 1px solid var(--line);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 10px 25px #18243d0b;
    }
    .intro-block .eyebrow { margin-bottom: 10px; }
    .intro-block h2 { font-size: clamp(1.85rem, 8vw, 2.5rem); }
    .intro-block p:last-child { font-size: .92rem; }
    .billing-tabs {
        width: 100%;
        padding: 5px;
        border-radius: 16px;
        background: #edf2f8;
        box-shadow: none;
    }
    .billing-tab {
        flex: 1 1 25%;
        min-width: 0;
        padding: 13px 7px;
        border-radius: 11px;
    }
    .billing-tab::before { display: none; }
    .billing-tab.is-active { box-shadow: 0 5px 12px #101d4330; }
    .billing-note {
        display: inline-block;
        position: relative;
        left: 50%;
        margin: 12px 0 16px;
        padding: 7px 13px;
        border: 1px solid #d7e5fa;
        border-radius: 999px;
        background: var(--blue-soft);
        transform: translateX(-50%);
        white-space: nowrap;
    }
    .plans { grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 18px; row-gap: 76px; }
    .plans .plan-card:last-child { grid-column: auto; left: auto; }
    .trust-strip { display: block; }
    .trust-strip div { flex-wrap: wrap; margin-top: 12px; }
    .site-footer { flex-direction: column; gap: 5px; }
    .home-hero { padding: 76px 0 82px; }
    .home-hero h1 { font-size: clamp(2.7rem, 12vw, 4rem); }
    .hosting-options { padding: 64px 0 76px; }
    .hosting-option-grid { grid-template-columns: 1fr; }
}

@media (min-width: 721px) and (max-width: 1000px) {
    .plans { grid-template-columns: repeat(4, minmax(0, 1fr)); column-gap: 14px; }
}

@media (max-width: 430px) {
    .page-width { width: min(100% - 24px, 1280px); }
    .site-header { min-height: 64px; }
    .page-banner { padding: 38px 0 34px; }
    .page-banner h1 { margin-bottom: 18px; font-size: 2.35rem; }
    .breadcrumb { font-size: .78rem; }
    .intro-block { padding: 21px 17px 20px; }
    .intro-block h2 { font-size: 2rem; }
    .billing-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 4px; padding: 5px; }
    .billing-tab { width: 100%; }
    .billing-note { max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
    .billing-note { margin-bottom: 14px; font-size: .76rem; }
}

/* Modern plan cards: compact header badges replace the oversized circles. */
.plan-card {
    padding: 26px 24px 24px;
    border-top: 4px solid var(--blue);
    border-radius: 16px;
}
.plan-card h3.plan-circle {
    position: static;
    width: fit-content;
    height: auto;
    min-height: 0;
    margin: 0 0 18px;
    padding: 8px 13px;
    border-radius: 8px;
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    transform: none;
}
.plan-card .plan-circle-standard { background: #075989; }
.plan-card .plan-circle-advanced { background: var(--navy); }
.plan-card .plan-circle-premium { background: #3f4a61; }
.plan-card .plan-circle-enterprise { background: #5c3b80; }
.plans { margin-top: 45px; row-gap: 34px; }

/* VPS cards use a compact infrastructure-console style distinct from shared hosting. */
.vps-theme .plan-card {
    padding: 24px 24px 22px;
    border: 1px solid #26375a;
    border-top: 1px solid #26375a;
    border-radius: 12px;
    background: #111c38;
    color: #f5f7fc;
    box-shadow: 0 14px 28px #101d431c;
}
.vps-theme .plan-card:hover {
    border-color: #4b8df2;
    box-shadow: 0 18px 34px #101d432e;
}
.vps-theme .plan-card h3.plan-circle {
    margin-bottom: 22px;
    border: 1px solid #49638f;
    background: transparent;
    color: #b8e36b;
    font-size: .72rem;
    letter-spacing: .12em;
}
.vps-theme .plan-description {
    min-height: 24px;
    color: #aebbd3;
    font-size: .92rem;
}
.vps-theme .price {
    margin-bottom: 18px;
    color: #fff;
}
.vps-theme .currency { color: #7db0ff; }
.vps-theme .price small { color: #9aa9c3; }
.vps-theme .purchase-button {
    margin-top: 14px;
    border-color: #4b8df2;
    background: #2f73e8;
    box-shadow: 0 8px 18px #07163466;
}
.vps-theme .purchase-button:hover {
    border-color: #7db0ff;
    background: #4387f0;
}
.vps-theme .plan-card > ul:not(.extra-features) {
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid #2b3b5c;
}
.vps-theme .plan-card > ul:not(.extra-features) li {
    color: #b6c2d7;
}
.vps-theme .plan-card > ul:not(.extra-features) strong {
    color: #fff;
}
.vps-theme .features-toggle {
    color: #9fc2ff;
}

/* Reference-inspired VPS cards: white panels with overlapping plan discs. */
.vps-theme .plan-card {
    padding: 78px 30px 0;
    border: 0;
    border-radius: 0;
    background: #fff;
    color: var(--ink);
    box-shadow: 0 12px 24px #18243d26;
    overflow: visible;
}
.vps-theme .plan-card:hover {
    border-color: transparent;
    box-shadow: 0 16px 30px #18243d33;
}
.vps-theme .plan-card h3.plan-circle {
    position: absolute;
    top: -48px;
    left: 50%;
    display: grid;
    width: 96px;
    height: 96px;
    margin: 0;
    padding: 0;
    place-items: center;
    border: 0;
    border-radius: 50%;
    color: #fff;
    font-size: 1rem;
    font-weight: 500;
    letter-spacing: 0;
    text-transform: none;
    transform: translateX(-50%);
}
.vps-theme .plan-circle-basic { background: #1768ed; }
.vps-theme .plan-circle-standard { background: #075589; }
.vps-theme .plan-circle-advanced { background: #2d2d2f; }
.vps-theme .plan-circle-premium { background: #4a4a4d; }
.vps-theme .plan-circle-enterprise { background: #5c3b80; }
.vps-theme .plan-description {
    display: none;
}
.vps-theme .price {
    justify-content: center;
    margin: 0 0 18px;
    color: #202027;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 2rem;
    font-weight: 400;
    letter-spacing: 0;
}
.vps-theme .currency {
    color: #202027;
    font-size: 1.7rem;
    font-weight: 400;
}
.vps-theme .price small {
    position: absolute;
    margin: 29px 0 0 2px;
    color: #4f5663;
    font-family: Arial, Helvetica, sans-serif;
    font-size: .48rem;
    letter-spacing: 0;
}
.vps-theme .purchase-button {
    display: flex;
    justify-content: center;
    width: 112px;
    min-height: 29px;
    margin: 24px auto -14px;
    padding: 6px 10px;
    border: 1px solid #1768ed;
    border-radius: 0;
    background: #1768ed;
    box-shadow: none;
    font-size: .62rem;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.vps-theme .purchase-button > span {
    font-size: .62rem;
}
.vps-theme .purchase-button .cart-icon,
.vps-theme .purchase-button .arrow {
    display: none;
}
.vps-theme .purchase-button:hover {
    border-color: #0e4ebd;
    background: #0e4ebd;
    box-shadow: 0 5px 12px #18243d26;
}
.vps-theme .plan-card > ul:not(.extra-features) {
    gap: 0;
    margin: 0;
    padding: 0;
    border-top: 0;
    color: #555b65;
    font-size: .7rem;
}
.vps-theme .plan-card > ul:not(.extra-features) li {
    min-height: 29px;
    padding: 6px 0;
    border-bottom: 1px solid #cfd2d5;
    color: #555b65;
    white-space: nowrap;
}
.vps-theme .plan-card > ul:not(.extra-features) li::before {
    width: 16px;
    height: 16px;
    margin-right: 8px;
    border-radius: 0;
    background: transparent;
    color: #378d2a;
    font-size: .95rem;
    box-shadow: none;
}
.vps-theme .plan-card > ul:not(.extra-features) li.feature-unavailable::before {
    background: transparent;
    color: #f04444;
}
.vps-theme .plan-card > ul:not(.extra-features) strong {
    color: #555b65;
    font-weight: 400;
}
.vps-theme .features-toggle {
    display: none;
}
.vps-theme .popular-badge {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    min-height: 48px;
    align-items: center;
    justify-content: center;
    border-radius: 12px 12px 0 0;
    background: linear-gradient(100deg, #4c39f2, #8522ee);
    color: #fff;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.vps-theme .plan-card-featured {
    padding-top: 78px;
    background: #edf1ff;
}
.vps-theme .plan-card-featured h3.plan-circle {
    position: static;
    display: block;
    width: auto;
    height: auto;
    margin: 0 0 18px;
    color: #101d43;
    font-size: 1.45rem;
    font-weight: 700;
    text-align: left;
    transform: none;
}
.vps-theme .plan-card-featured .price {
    justify-content: flex-start;
    color: #4b3bea;
}
.vps-theme .plan-card-featured .currency {
    color: #4b3bea;
}
.vps-theme .plan-card-featured .purchase-button {
    background: #4b3bea;
    border-color: #4b3bea;
}

/* Final reference-aligned VPS card treatment. */
.vps-theme .plans {
    align-items: start;
    column-gap: 42px;
    row-gap: 132px;
    margin-top: 0;
    overflow: visible;
}
.vps-theme .billing-note {
    margin-bottom: 112px;
}
.vps-theme .plans .plan-card:last-child {
    grid-column: 2 / span 2;
    width: calc(50% - 21px);
    justify-self: center;
    left: auto;
}
.vps-theme .plan-card,
.vps-theme .plan-card-featured {
    min-height: 538px;
    padding: 126px 42px 0;
    border: 0;
    border-radius: 0;
    background: #fff;
    box-shadow: 0 11px 26px #18243d2b;
    color: var(--ink);
}
.vps-theme .plan-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 30px #18243d38;
}
.vps-theme .plan-card h3.plan-circle,
.vps-theme .plan-card-featured h3.plan-circle {
    position: absolute;
    top: -96px;
    left: 50%;
    display: grid;
    width: 192px;
    height: 192px;
    margin: 0;
    padding: 0;
    place-items: center;
    border-radius: 50%;
    color: #fff;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 1.75rem;
    font-weight: 400;
    letter-spacing: 0;
    text-transform: none;
    text-align: center;
    transform: translateX(-50%);
    z-index: 4;
    opacity: 1;
    visibility: visible;
}
.vps-theme .plan-circle-basic { background: #1168ee !important; }
.vps-theme .plan-circle-standard { background: #07598a !important; }
.vps-theme .plan-circle-advanced { background: #333335 !important; }
.vps-theme .plan-circle-premium { background: #3a3a3c !important; }
.vps-theme .plan-circle-enterprise { background: #51416e !important; }
.vps-theme .plan-description,
.vps-theme .popular-badge {
    display: none;
}
.vps-theme .price,
.vps-theme .plan-card-featured .price {
    position: relative;
    justify-content: center;
    margin: 0 0 30px;
    color: #24262a;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 3rem;
    font-weight: 300;
    letter-spacing: -.04em;
}
.vps-theme .currency,
.vps-theme .plan-card-featured .currency {
    margin-right: 3px;
    color: #24262a;
    font-size: 2.4rem;
    font-weight: 300;
}
.vps-theme .price small,
.vps-theme .plan-card-featured .price small {
    position: static;
    align-self: center;
    margin: 10px 0 0 8px;
    color: #667085;
    font-family: Arial, Helvetica, sans-serif;
    font-size: .82rem;
    font-weight: 400;
    letter-spacing: 0;
}
.vps-theme .plan-card > ul:not(.extra-features) {
    display: grid;
    order: 3;
    gap: 0;
    margin: 0;
    padding: 0;
    border-top: 0;
    color: #606773;
    font-size: .96rem;
    line-height: 1.4;
}
.vps-theme .plan-card > ul:not(.extra-features) li {
    min-height: 48px;
    padding: 13px 0;
    border-bottom: 1px solid #bfc2c6;
    color: #606773;
    white-space: nowrap;
}
.vps-theme .plan-card > ul:not(.extra-features) li::before {
    width: 20px;
    height: 20px;
    margin-right: 12px;
    border-radius: 0;
    background: transparent;
    color: #378f2c;
    font-size: 1.1rem;
    box-shadow: none;
}
.vps-theme .plan-card > ul:not(.extra-features) li.feature-unavailable::before {
    background: transparent;
    color: #ff3e4b;
}
.vps-theme .plan-card > ul:not(.extra-features) strong {
    color: #111827;
    font-weight: 800;
}
.vps-theme .purchase-button {
    order: 5;
    display: flex;
    width: 100%;
    min-height: 58px;
    margin: 24px 0 -29px;
    padding: 0 12px;
    border: 0;
    border-radius: 0;
    background: #1168ee;
    box-shadow: none;
    color: #fff;
    font-size: .9rem;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}
.vps-theme .plan-card:nth-child(2) .purchase-button { background: #07598a; }
.vps-theme .plan-card:nth-child(3) .purchase-button { background: #333335; }
.vps-theme .plan-card:nth-child(4) .purchase-button { background: #3a3a3c; }
.vps-theme .plan-card:nth-child(5) .purchase-button { background: #51416e; }
.vps-theme .purchase-button > span { font-size: .9rem; }
.vps-theme .purchase-button .cart-icon,
.vps-theme .purchase-button .arrow { display: none; }
.vps-theme .purchase-button:hover {
    filter: brightness(.9);
    transform: translateY(-1px);
}
.vps-theme .features-toggle {
    order: 4;
    display: flex;
    min-height: 38px;
    margin: 14px auto 0;
    padding: 8px 14px;
    border: 1px solid #d4dbe6;
    border-radius: 6px;
    background: #f8fafc;
    color: #315a9b;
    font-size: .78rem;
    font-weight: 700;
}
.vps-theme .features-toggle:hover {
    border-color: #9bb8e8;
    background: #edf4ff;
    color: #1168ee;
}

.vps-theme ~ .features-dialog .dialog-features {
    color: #667085;
}
.vps-theme ~ .features-dialog .dialog-category h3 {
    color: #14284b;
    font-size: 1.05rem;
}
.vps-theme ~ .features-dialog .dialog-category li {
    min-height: 48px;
    border-bottom-color: #dbe5ed;
    font-size: 1rem;
}
.vps-theme ~ .features-dialog .dialog-features li::before {
    width: 20px;
    height: 20px;
    flex-basis: 20px;
    margin-right: 11px;
    border-radius: 0;
    background: transparent;
    color: #378f2c;
    font-size: 1.1rem;
    box-shadow: none;
}
.vps-theme ~ .features-dialog .dialog-features li.feature-unavailable::before {
    background: transparent;
    color: #ff3e4b;
    font-size: 1rem;
}
.vps-theme ~ .features-dialog .dialog-features li strong {
    color: #111827;
    font-weight: 800;
}
.vps-theme ~ .features-dialog .dialog-features li:not(.feature-unavailable) {
    color: #667085;
}
.vps-theme ~ .features-dialog .dialog-category + .dialog-category {
    margin-top: 24px;
}

@media (max-width: 720px) {
    .vps-theme .plans {
        row-gap: 112px;
        margin-top: 0;
    }
    .vps-theme .billing-note {
        margin-bottom: 88px;
    }
    .vps-theme .plans .plan-card:last-child {
        grid-column: auto;
        width: auto;
        justify-self: stretch;
    }
    .vps-theme .plan-card,
    .vps-theme .plan-card-featured {
        min-height: 510px;
        padding: 116px 28px 0;
    }
    .vps-theme .plan-card h3.plan-circle,
    .vps-theme .plan-card-featured h3.plan-circle {
        top: -78px;
        width: 156px;
        height: 156px;
        font-size: 1.35rem;
    }
}

/* Dedicated hosting: compact, rounded cards inspired by the supplied reference. */
.dedicated-theme .plans {
    align-items: start;
    column-gap: 20px;
    row-gap: 30px;
    margin-top: 28px;
}
.dedicated-theme .plan-card,
.dedicated-theme .plan-card-featured {
    min-height: 390px;
    padding: 28px 24px 0;
    border: 2px solid #e2e8ef;
    border-radius: 17px;
    background: #fff;
    box-shadow: 0 5px 0 #e6ebef, 0 14px 25px #18243d0b;
    color: var(--ink);
}
.dedicated-theme .plan-card:hover {
    border-color: #0497ce;
    transform: translateY(-3px);
    box-shadow: 0 5px 0 #e1e8ed, 0 18px 30px #18243d14;
}
.dedicated-theme .plan-card h3.plan-circle,
.dedicated-theme .plan-card-featured h3.plan-circle {
    position: static;
    display: block;
    width: auto;
    height: auto;
    margin: 0 0 18px;
    color: #718096;
    font-size: .9rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-align: left;
    text-transform: uppercase;
    transform: none;
}
.dedicated-theme .plan-circle-basic,
.dedicated-theme .plan-circle-standard,
.dedicated-theme .plan-circle-advanced,
.dedicated-theme .plan-circle-premium,
.dedicated-theme .plan-circle-enterprise {
    background: transparent !important;
}
.dedicated-theme .plan-description,
.dedicated-theme .popular-badge {
    display: none;
}
.dedicated-theme .price,
.dedicated-theme .plan-card-featured .price {
    justify-content: flex-start;
    margin: 0 0 20px;
    color: #101d2f;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 2.7rem;
    font-weight: 800;
    letter-spacing: -.05em;
}
.dedicated-theme .currency,
.dedicated-theme .plan-card-featured .currency {
    margin-right: 2px;
    color: #101d2f;
    font-size: 1.7rem;
    font-weight: 800;
}
.dedicated-theme .price small,
.dedicated-theme .plan-card-featured .price small {
    position: static;
    align-self: end;
    margin: 0 0 6px 5px;
    color: #667085;
    font-size: .82rem;
    font-weight: 400;
}
.dedicated-theme .plan-card > ul:not(.extra-features) {
    display: grid;
    order: 3;
    gap: 0;
    margin: 0;
    padding: 13px 0 0;
    border-top: 1px solid #e0e6ec;
    color: #4d5b6f;
    font-size: .96rem;
}
.dedicated-theme .plan-card > ul:not(.extra-features) li {
    min-height: 25px;
    padding: 4px 0;
    border: 0;
    color: #4d5b6f;
    white-space: normal;
}
.dedicated-theme .plan-card > ul:not(.extra-features) li::before {
    width: 19px;
    height: 19px;
    margin-right: 9px;
    border-radius: 50%;
    background: #dff4fb;
    color: #0394c9;
    font-size: .82rem;
    box-shadow: none;
}
.dedicated-theme .plan-card > ul:not(.extra-features) li.feature-unavailable::before {
    background: #fff0f0;
    color: #ef5350;
}
.dedicated-theme .plan-card > ul:not(.extra-features) strong {
    color: #111827;
    font-weight: 800;
}
.dedicated-theme .features-toggle {
    order: 4;
    display: flex;
    min-height: 38px;
    margin: 13px auto 0;
    padding: 8px 14px;
    border: 2px solid #0497ce;
    border-radius: 9px;
    background: #fff;
    color: #0589b8;
    font-size: .88rem;
    font-weight: 800;
}
.dedicated-theme .features-toggle:hover {
    background: #e9f8fc;
    color: #047399;
}
.dedicated-theme .purchase-button {
    order: 5;
    display: flex;
    justify-content: center;
    min-height: 42px;
    margin: 14px 0 -21px;
    border: 2px solid #0497ce;
    border-radius: 9px;
    background: #0497ce;
    box-shadow: none;
    color: #fff;
    font-size: .9rem;
    font-weight: 800;
}
.dedicated-theme .plan-card-featured {
    border-color: #e2e8ef;
    box-shadow: 0 5px 0 #e6ebef, 0 14px 25px #18243d0b;
}
.dedicated-theme .plan-card-featured .purchase-button {
    border-color: #0497ce;
    background: #0497ce;
    color: #fff;
}
.dedicated-theme .purchase-button > span { font-size: .9rem; }
.dedicated-theme .purchase-button .cart-icon,
.dedicated-theme .purchase-button .arrow { display: none; }

.checkout-page {
    padding: 72px 0 90px;
}
.checkout-header {
    max-width: 680px;
    margin: 0 auto 42px;
    text-align: center;
}
.checkout-header h1 {
    margin: 0 0 14px;
    color: var(--navy);
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    letter-spacing: -.06em;
}
.checkout-header > p:last-child {
    margin: 0;
    color: var(--muted);
    font-size: 1.05rem;
}
.checkout-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(280px, .8fr);
    gap: 24px;
    align-items: start;
}
.checkout-panel,
.order-summary {
    padding: 32px;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 14px 32px #18243d0b;
}
.checkout-panel h2,
.order-summary h2 {
    margin: 0 0 8px;
    color: var(--navy);
    font-size: 1.45rem;
}
.checkout-muted,
.summary-note {
    margin: 0;
    color: var(--muted);
}
.domain-search {
    margin-top: 28px;
}
.domain-search label {
    display: block;
    margin-bottom: 8px;
    color: var(--navy);
    font-size: .9rem;
    font-weight: 700;
}
.domain-search-row {
    display: flex;
    align-items: stretch;
    gap: 10px;
}
.domain-search-row input {
    min-width: 0;
    flex: 1 1 240px;
    min-height: 48px;
    padding: 0 14px;
    border: 1px solid #cdd8e7;
    border-radius: 9px;
    color: var(--ink);
    font: inherit;
}
.domain-search-row select {
    flex: 0 0 112px;
    min-height: 52px;
    min-width: 112px;
    padding: 0 36px 0 16px;
    border: 1px solid #c8d5e5;
    border-radius: 12px;
    appearance: none;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath d='m4 6 4 4 4-4' fill='none' stroke='%23101d43' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-position: right 13px center;
    background-repeat: no-repeat;
    color: var(--ink);
    font: inherit;
    font-size: 1.02rem;
    font-weight: 700;
    cursor: pointer;
    transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
}
.domain-search-row select:focus {
    border-color: var(--blue);
    outline: 0;
    box-shadow: 0 0 0 4px #2f73e81c;
}
.domain-search-row select:hover {
    border-color: #9fb5d0;
    background-color: #fbfdff;
}
.domain-search-row input:focus {
    outline: 3px solid #2f73e833;
    border-color: var(--blue);
}
.domain-search-row button,
.checkout-continue {
    flex: 0 0 auto;
    min-height: 48px;
    padding: 0 18px;
    border: 0;
    border-radius: 9px;
    background: var(--blue);
    color: #fff;
    cursor: pointer;
    font-weight: 800;
}
.domain-options {
    display: grid;
    gap: 12px;
    margin-top: 24px;
}
.domain-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    border: 1px solid #d7e0eb;
    border-radius: 10px;
    background: #fff;
    color: var(--ink);
    cursor: pointer;
    text-align: left;
    font: inherit;
}
.domain-option.is-selected {
    border-color: var(--blue);
    background: var(--blue-soft);
    box-shadow: 0 0 0 3px #2f73e81a;
}
.domain-option-icon {
    display: grid;
    width: 28px;
    height: 28px;
    place-items: center;
    border-radius: 50%;
    background: #d9e8ff;
    color: var(--blue);
    font-size: 1.1rem;
    font-weight: 700;
}
.domain-option strong,
.domain-option small {
    display: block;
}
.domain-option small {
    margin-top: 3px;
    color: var(--muted);
}
.domain-mode-fields[hidden] {
    display: none;
}
.domain-selection-note {
    margin: 14px 0 0;
    color: var(--muted);
    font-size: .88rem;
}
.checkout-continue {
    width: 100%;
    margin-top: 24px;
}
.order-summary {
    background: var(--navy);
    color: #fff;
}
.order-summary .eyebrow {
    color: var(--lime);
}
.order-summary h2 {
    color: #fff;
}
.order-summary dl {
    margin: 24px 0;
    border-top: 1px solid #ffffff2b;
}
.order-summary dl div {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    padding: 14px 0;
    border-bottom: 1px solid #ffffff2b;
}
.order-summary dt { color: #aebbd3; }
.order-summary dd {
    margin: 0;
    color: #fff;
    font-weight: 800;
}
.order-summary .summary-price-row {
    padding-top: 18px;
    padding-bottom: 18px;
}
.order-summary .summary-price-row dd {
    color: var(--lime);
    font-size: 1.18rem;
}
.order-summary .summary-note { color: #c2cee1; font-size: .9rem; }

@media (max-width: 720px) {
    .checkout-page { padding: 48px 0 60px; }
    .checkout-layout { grid-template-columns: 1fr; }
    .checkout-panel,
    .order-summary { padding: 24px 20px; }
    .domain-search-row { display: grid; }
    .domain-search-row select,
    .domain-search-row button { width: 100%; }
}

.dedicated-theme ~ .features-dialog .dialog-features {
    color: #5f6b7d;
}
.dedicated-theme ~ .features-dialog .dialog-category h3 {
    color: #14284b;
}
.dedicated-theme ~ .features-dialog .dialog-category li {
    min-height: 48px;
    border-bottom-color: #dbe5ed;
    font-size: 1rem;
}
.dedicated-theme ~ .features-dialog .dialog-features li::before {
    width: 20px;
    height: 20px;
    flex-basis: 20px;
    margin-right: 11px;
    border-radius: 6px;
    background: #dff4fb;
    color: #0497ce;
    font-size: .78rem;
}
.dedicated-theme ~ .features-dialog .dialog-features li.feature-unavailable::before {
    background: #fff0f0;
    color: #ef5350;
    font-size: 1rem;
}
.dedicated-theme ~ .features-dialog .dialog-features li strong {
    color: #142033;
    font-weight: 800;
}
.dedicated-theme ~ .features-dialog .dialog-category + .dialog-category {
    margin-top: 24px;
}

@media (max-width: 720px) {
    .dedicated-theme .plans {
        row-gap: 30px;
        margin-top: 28px;
    }
    .dedicated-theme .plan-card,
    .dedicated-theme .plan-card-featured {
            min-height: 420px;
    }
}

    /* Shared hosting card refresh */
    .shared-theme .plans {
        column-gap: 18px;
        row-gap: 48px;
        margin-top: 48px;
    }
    .shared-theme .plan-card,
    .shared-theme .plan-card-featured {
        min-height: 690px;
        padding: 28px 22px 22px;
        border: 1px solid #dfe5ee;
        border-top: 4px solid #2f73e8;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 8px 22px #18243d0b;
        overflow: visible;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .shared-theme .plan-card:hover {
        transform: translateY(-4px);
        border-color: #c9d5e5;
        box-shadow: 0 16px 30px #18243d16;
    }
    .shared-theme .plan-card-featured {
        border-color: #aebef2;
        border-top-color: #4b4be8;
        box-shadow: 0 12px 28px #4b4be826;
    }
    .shared-theme .plan-card-featured:hover { border-color: #8fa6ed; }
    .shared-theme .plan-card h3.plan-circle {
        position: static;
        display: inline-flex;
        width: fit-content;
        height: auto;
        min-height: 34px;
        margin: 0 0 16px;
        padding: 8px 12px;
        place-items: center;
        border-radius: 7px;
        color: #fff;
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .1em;
        line-height: 1;
        text-transform: uppercase;
        transform: none;
    }
    .shared-theme .plan-circle-basic { background: #2f73e8; }
    .shared-theme .plan-circle-standard { background: #19708d; }
    .shared-theme .plan-circle-advanced { background: #4b4be8; }
    .shared-theme .plan-circle-premium { background: #3d4859; }
    .shared-theme .plan-circle-enterprise { background: #69449b; }
    .shared-theme .popular-badge {
        position: absolute;
        top: -13px;
        right: 18px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #b8e36b;
        color: #20300d;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .shared-theme .plan-description {
        min-height: 24px;
        margin: 0 0 12px;
        color: #667085;
        font-size: .94rem;
    }
    .shared-theme .price {
        margin-bottom: 16px;
        color: #101d43;
        font-size: 2.35rem;
    }
    .shared-theme .currency { color: #2f73e8; }
    .shared-theme .purchase-button {
        min-height: 48px;
        margin-top: 12px;
        border: 0;
        border-radius: 8px;
        background: #2f73e8;
        box-shadow: 0 7px 14px #2f73e833;
    }
    .shared-theme .purchase-button:hover {
        border: 0;
        background: #101d43;
        box-shadow: 0 10px 18px #101d4330;
        filter: none;
    }
    .shared-theme .feature-groups {
        max-height: 350px;
        margin-top: 20px;
        padding-top: 18px;
        border-top-color: #e5eaf1;
    }
    .shared-theme .feature-group + .feature-group { margin-top: 18px; }
    .shared-theme .feature-group h4 {
        margin-bottom: 8px;
        color: #344054;
        font-size: .72rem;
        letter-spacing: .1em;
    }
    .shared-theme .feature-group ul { gap: 10px; font-size: .9rem; }
    .shared-theme .plan-card li::before {
        width: 17px;
        height: 17px;
        margin-right: 7px;
        border-radius: 5px;
        background: #eaf2ff;
        color: #2f73e8;
        box-shadow: none;
    }
    .shared-theme .plan-card li.feature-unavailable {
        color: #6f7b91;
    }
    .shared-theme .plan-card li.feature-unavailable::before {
        background: #fff1f1;
        color: #e53935;
    }
    .shared-theme .features-toggle {
        margin-top: 16px;
        padding: 8px 12px;
        border-color: #d7dfeb;
        background: #fff;
        color: #344054;
        font-size: .82rem;
    }
    .shared-theme .features-toggle:hover {
        border-color: #2f73e8;
        background: #f7faff;
        color: #2f73e8;
        box-shadow: none;
    }
    @media (max-width: 720px) {
        .shared-theme .plans { row-gap: 34px; margin-top: 32px; }
        .shared-theme .plan-card,
        .shared-theme .plan-card-featured { min-height: 660px; }
    }
    .shared-theme .plan-card {
        border-top: 3px solid #45c8c2;
        border-radius: 5px;
        box-shadow: 0 5px 18px #18243d0c;
    }
    .shared-theme .plan-card:hover {
        border-top-color: #2f73e8;
        box-shadow: 0 14px 28px #18243d18;
    }
    .shared-theme .plan-card-featured {
        border-top-color: #2f73e8;
        border-radius: 5px;
    }
    .shared-theme .cheapest-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        padding: 4px 12px;
        border: 1px solid #8fe4dc;
        border-radius: 999px;
        background: #e9fffc;
        color: #168d87;
        font-size: .65rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        transform: translateX(-50%);
    }
    .shared-theme .plan-card h3.plan-circle {
        display: flex;
        width: 100%;
        min-height: 0;
        margin: 2px 0 14px;
        padding: 0;
        justify-content: center;
        border-radius: 0;
        background: transparent;
        color: #2b2378;
        font-size: 1.45rem;
        letter-spacing: -.03em;
    }
    .shared-theme .plan-description {
        text-align: center;
    }
    .shared-theme .price {
        justify-content: center;
        margin-bottom: 10px;
        font-size: 2.6rem;
    }
    .shared-theme .currency {
        color: #3fc8be;
        font-size: 1.25rem;
    }
    .shared-theme .price small {
        color: #7890a0;
    }
    .shared-theme .purchase-button {
        width: min(100%, 220px);
        align-self: center;
        border-radius: 5px;
        background: #2b2378;
        box-shadow: 0 7px 14px #2b23782b;
    }
    .shared-theme .purchase-button:hover {
        background: #168d87;
        box-shadow: 0 9px 18px #168d8730;
    }
    .shared-theme .feature-groups {
        margin-top: 24px;
        padding-top: 20px;
        border-top-color: #edf0f4;
    }
    .shared-theme .feature-group h4 {
        color: #2b2378;
    }
    .shared-theme .plan-card.is-coming-soon .price-value {
        color: #2b2378;
        font-size: 2rem;
        letter-spacing: -.02em;
    }
    .shared-theme .plan-card.is-coming-soon .price-unit {
        display: none;
    }
    .shared-theme .plan-card.is-coming-soon .purchase-button {
        background: #e8edf4;
        color: #667085;
        cursor: not-allowed;
        box-shadow: none;
    }
    .shared-theme .plan-card.is-coming-soon .purchase-button:hover {
        background: #e8edf4;
        color: #667085;
        transform: none;
    }
    .shared-theme .plan-card h3.plan-circle {
        font-size: 1.65rem;
    }
    .shared-theme .plan-description {
        font-size: 1.05rem;
    }
    .shared-theme .price {
        font-size: 2.85rem;
    }
    .shared-theme .price small {
        font-size: 1rem;
    }
    .shared-theme .purchase-button {
        min-height: 52px;
        font-size: 1rem;
    }
    .shared-theme .feature-group h4 {
        font-size: .8rem;
    }
    .shared-theme .feature-group ul {
        font-size: 1rem;
        line-height: 1.5;
    }
    .shared-theme .features-toggle {
        font-size: .9rem;
    }
    .shared-theme .cheapest-badge,
    .shared-theme .popular-badge {
        font-size: .72rem;
    }

    .shared-hosting-page {
        --navy: #0f172a;
        --navy-deep: #0f172a;
        --blue: #2563eb;
        --blue-2: #1d4ed8;
        --blue-soft: #eff6ff;
        --ink: #0f172a;
        --muted: #94a3b8;
        --paper: #f8fafc;
        --line: #e5e7eb;
        --soft: #f8fafc;
        background: #fff;
        color: var(--ink);
    }

    .shared-hosting-page .site-top {
        min-height: 36px;
        background: var(--soft);
        border-bottom: 1px solid var(--line);
        color: #475569;
        font-size: 13px;
    }

    .shared-hosting-page .site-top-inner,
    .shared-hosting-page .site-header-inner {
        width: auto;
        max-width: 1140px;
        padding-right: 24px;
        padding-left: 24px;
        margin: 0 auto;
    }

    .shared-hosting-page .site-top-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 36px;
    }

    .shared-hosting-page .site-top-links {
        display: flex;
        align-items: center;
        gap: 22px;
    }

    .shared-hosting-page .site-top-links a {
        color: #475569;
    }

    .shared-hosting-page .site-top-links a:hover {
        color: var(--blue);
    }

    .shared-hosting-page .site-header {
        position: sticky;
        top: 0;
        z-index: 20;
        width: 100%;
        min-height: 72px;
        padding: 0;
        background: #ffffffeF;
        border-bottom: 1px solid var(--line);
        backdrop-filter: blur(8px);
    }

    .shared-hosting-page .site-header-inner {
        display: flex;
        align-items: center;
        gap: 28px;
        height: 72px;
    }

    .shared-hosting-page .site-logo {
        display: inline-flex;
        align-items: center;
        flex: 0 0 auto;
    }

    .shared-hosting-page .site-logo .ct-logo-img {
        width: auto;
        height: 50px;
        object-fit: contain;
    }

    .shared-hosting-page .site-nav {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 1;
    }

    .shared-hosting-page .site-nav a,
    .shared-hosting-page .site-nav-dropdown summary {
        padding: 8px 13px;
        border-radius: 8px;
        color: #475569;
        font-size: 15px;
        font-weight: 500;
    }

    .shared-hosting-page .site-nav a:hover,
    .shared-hosting-page .site-nav a.selected,
    .shared-hosting-page .site-nav-dropdown[open] summary {
        background: var(--soft);
        color: var(--ink);
    }

    .shared-hosting-page .site-nav-dropdown {
        position: relative;
    }

    .shared-hosting-page .site-submenu {
        top: 100%;
        left: 0;
    }

    .shared-hosting-page .site-account {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .shared-hosting-page .site-btn,
    .shared-hosting-page .site-btn-ghost {
        border-radius: 8px;
    }

    .shared-hosting-page .page-banner {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        padding: 70px 0 62px;
        background:
            radial-gradient(circle at 85% 20%, #2563eb55 0, transparent 34%),
            linear-gradient(135deg, #0f172a 0%, #1e3a8a 58%, #2563eb 100%);
        color: #fff;
        animation: shared-banner-enter .7s ease both;
    }

    .shared-hosting-page .page-banner::before,
    .shared-hosting-page .page-banner::after {
        position: absolute;
        z-index: -1;
        border-radius: 999px;
        content: "";
        pointer-events: none;
    }

    .shared-hosting-page .page-banner::before {
        top: -130px;
        right: 8%;
        width: 390px;
        height: 390px;
        border: 1px solid #93c5fd55;
        box-shadow: 0 0 0 28px #93c5fd0d, 0 0 0 56px #93c5fd08;
        animation: shared-banner-orbit 12s ease-in-out infinite;
    }

    .shared-hosting-page .page-banner::after {
        right: 25%;
        bottom: -90px;
        width: 210px;
        height: 210px;
        background: #60a5fa22;
        filter: blur(4px);
        animation: shared-banner-glow 7s ease-in-out infinite alternate;
    }

    .shared-hosting-page .page-banner .page-width {
        position: relative;
        z-index: 1;
        animation: shared-banner-content .8s .1s ease both;
    }

    .shared-hosting-page .breadcrumb a {
        color: #bfdbfe;
    }

    .shared-hosting-page .breadcrumb strong {
        color: #fff;
    }

    @keyframes shared-banner-enter {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes shared-banner-content {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes shared-banner-orbit {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
        50% { transform: translate3d(-22px, 15px, 0) rotate(8deg); }
    }

    @keyframes shared-banner-glow {
        from { transform: translate3d(-20px, 0, 0) scale(.92); opacity: .45; }
        to { transform: translate3d(24px, -12px, 0) scale(1.08); opacity: .9; }
    }

    @media (prefers-reduced-motion: reduce) {
        .shared-hosting-page .page-banner,
        .shared-hosting-page .page-banner::before,
        .shared-hosting-page .page-banner::after,
        .shared-hosting-page .page-banner .page-width {
            animation: none;
        }
    }

    @media (max-width: 760px) {
        .shared-hosting-page .site-top-inner {
            display: none;
        }

        .shared-hosting-page .site-header-inner {
            width: 100%;
            max-width: none;
            padding-right: 14px;
            padding-left: 14px;
            gap: 10px;
        }

        .shared-hosting-page .site-nav > a:not(:first-child),
        .shared-hosting-page .site-nav-dropdown {
            display: none;
        }

        .shared-hosting-page .page-banner {
            padding: 54px 0 48px;
        }
    }
    </style>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ md5_file(public_path('css/home.css')) }}">
</head>
<body class="site shared-hosting-page">
    <div class="site-top">
        <div class="site-wrap site-top-inner">
            <div class="site-top-links">
                <a href="{{ route('under.construction', 'blog') }}">Blog</a>
                <a href="{{ route('under.construction', 'offers') }}">Offers</a>
                <span>☎ {{ \App\Models\Setting::get('support_phone') }}</span>
            </div>
            <div class="site-top-links">
                <a class="site-admin-login" href="{{ route('admin.login') }}">Admin login</a>
                <a href="{{ route('cart.index') }}">🛒 Cart <b class="site-count">{{ count(session('domain_cart', [])) }}</b></a>
            </div>
        </div>
    </div>
    <header class="site-header">
        <div class="site-wrap site-header-inner">
            <a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a>
            <nav class="site-nav">
                <a class="selected" href="{{ route('landing') }}">Home</a>
                <a href="{{ route('domains.index') }}">Domains</a>
                <details class="site-nav-dropdown">
                    <summary>Web Hosting</summary>
                    <div class="site-submenu">
                        <a href="{{ route('hosting.shared') }}">Shared Hosting</a>
                        <a href="{{ route('under.construction', 'vps-hosting') }}">VPS Hosting</a>
                        <a href="{{ route('under.construction', 'dedicated-hosting') }}">Dedicated Hosting</a>
                    </div>
                </details>
                <a href="{{ route('under.construction', 'email-hosting') }}">Email</a>
                <a href="{{ route('support') }}">Support</a>
            </nav>
            <div class="site-account">
                @if(session('customer'))
                    @include('partials.client-area-menu')
                    <button class="site-user" type="button" onclick="event.stopPropagation(); document.getElementById('accountMenu').classList.toggle('open');">
                        @if(session('customer.logo'))
                            <img class="site-avatar" src="{{ session('customer.logo') }}" alt="">
                        @else
                            <span class="site-avatar">{{ session('customer.initials') ?: \App\User::initials(session('customer.name')) }}</span>
                        @endif
                        <span>{{ session('customer.name') }}</span> ⌄
                    </button>
                    <div class="site-menu" id="accountMenu">
                        <div class="site-menu-head">
                            <strong>{{ session('customer.name') }}</strong>
                            <span>{{ session('customer.email') }}</span>
                        </div>
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                        <a href="{{ route('mystore') }}">My Store</a>
                        <a href="{{ route('wallet.index') }}">Wallet · ৳{{ number_format(session('customer.balance', 0), 2) }}</a>
                        <a href="{{ route('invoices') }}">Invoices</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
                    </div>
                @else
                    <a class="site-btn-ghost" href="{{ route('login') }}">Log in</a>
                    <a class="site-btn" href="{{ route('register') }}">Sign up</a>
                @endif
            </div>
        </div>
    </header>

    <section class="page-banner">
    <div class="page-width">
        <h1>Shared Hosting</h1>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('landing') }}">Home</a>
            <span>/</span>
            <strong>Shared Hosting</strong>
        </nav>
    </div>
</section>

<main class="pricing page-width shared-theme">
    <div class="intro-block">
        <p class="eyebrow">Fast, dependable, yours</p>
        <h2>Choose the rhythm that fits your growth.</h2>
        <p>Reliable shared hosting with simple controls, generous resources, and everything you need to grow online.</p>
    </div>

    <div class="billing-tabs" role="tablist" aria-label="Billing period">
        <button class="billing-tab is-active" type="button" role="tab" aria-selected="true" data-period="monthly">Monthly</button>
        <button class="billing-tab" type="button" role="tab" aria-selected="false" data-period="yearly">Yearly</button>
        <button class="billing-tab" type="button" role="tab" aria-selected="false" data-period="twoYear">2 Years</button>
        <button class="billing-tab" type="button" role="tab" aria-selected="false" data-period="threeYear">3 Years</button>
    </div>
    <p class="billing-note" id="billing-note">Flexible month-to-month billing</p>

    @if(false)
    <div class="plans" aria-label="Legacy hardcoded shared hosting plans">
        <article class="plan-card">
            <span class="cheapest-badge">Cheapest</span>
            <h3 class="plan-circle plan-circle-basic">Plan A</h3>
            <p class="plan-description">For small websites.</p>
            <div class="price"><span class="currency">৳</span><span class="price-value" data-monthly="300" data-yearly="3000" data-two-year="6000" data-three-year="9000">300</span><small class="price-unit">/ month</small></div>
            <a class="purchase-button" href="{{ route('register') }}">
                <svg class="cart-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M3.5 4h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.4L20.5 8H6.1M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Purchase Now</span>
                <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <ul>
                <li><strong data-monthly="10 GB" data-yearly="10 GB">10 GB</strong> NVMe storage</li>
                <li><strong data-monthly="300 GB" data-yearly="100 GB">300 GB</strong> bandwidth/month</li>
                <li><strong data-monthly="100" data-yearly="50">100</strong> FTP users</li>
                <li><strong data-monthly="3" data-yearly="3">3</strong> websites/addons</li>
                <li><strong data-monthly="100" data-yearly="50">100</strong> email accounts</li>
            </ul>
            <button class="features-toggle" type="button" aria-haspopup="dialog" aria-controls="features-dialog">See more</button>
            <ul class="extra-features" hidden>
                <li data-monthly="100 email mailing lists" data-yearly="50 email mailing lists">100 email mailing lists</li><li data-monthly="10 MySQL databases" data-yearly="10 MySQL databases">10 MySQL databases</li><li data-monthly="100 subdomains" data-yearly="50 subdomains">100 subdomains</li>                <li class="feature-unavailable" data-feature="ecommerce">eCommerce</li><li class="feature-unavailable">Remote database access</li><li class="feature-unavailable">PostgreSQL databases</li><li class="feature-unavailable">PhpPG Admin</li><li class="feature-unavailable">SSH access (Terminal)</li><li class="feature-unavailable">Shell access (Jailed)</li><li class="feature-unavailable">eAccelerator</li>
            </ul>
        </article>

        <article class="plan-card">
            <h3 class="plan-circle plan-circle-standard">Plan B</h3>
            <p class="plan-description">For growing websites.</p>
            <div class="price"><span class="currency">৳</span><span class="price-value" data-monthly="500" data-yearly="5000" data-two-year="10000" data-three-year="15000">500</span><small class="price-unit">/ month</small></div>
            <a class="purchase-button" href="{{ route('register') }}">
                <svg class="cart-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M3.5 4h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.4L20.5 8H6.1M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Purchase Now</span>
                <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <ul>
                <li><strong data-monthly="20 GB" data-yearly="20 GB">20 GB</strong> NVMe storage</li>
                <li><strong data-monthly="500 GB" data-yearly="200 GB">500 GB</strong> bandwidth/month</li>
                <li><strong data-monthly="150" data-yearly="100">150</strong> FTP users</li>
                <li><strong data-monthly="5" data-yearly="5">5</strong> websites/addons</li>
                <li><strong data-monthly="200" data-yearly="100">200</strong> email accounts</li>
            </ul>
            <button class="features-toggle" type="button" aria-haspopup="dialog" aria-controls="features-dialog">See more</button>
            <ul class="extra-features" hidden>
                <li data-monthly="200 email mailing lists" data-yearly="100 email mailing lists">200 email mailing lists</li><li data-monthly="50 MySQL databases" data-yearly="20 MySQL databases">50 MySQL databases</li><li data-monthly="100 subdomains" data-yearly="100 subdomains">100 subdomains</li>                <li class="feature-unavailable" data-feature="ecommerce">eCommerce</li><li class="feature-unavailable">Remote database access</li><li class="feature-unavailable">PostgreSQL databases</li><li class="feature-unavailable">PhpPG Admin</li><li class="feature-unavailable">SSH access (Terminal)</li><li class="feature-unavailable">Shell access (Jailed)</li><li class="feature-unavailable">eAccelerator</li>
            </ul>
        </article>

        <article class="plan-card plan-card-featured">
            <span class="popular-badge">Most popular</span>
            <h3 class="plan-circle plan-circle-advanced">Plan C</h3>
            <p class="plan-description">For busy websites.</p>
            <div class="price"><span class="currency">৳</span><span class="price-value" data-monthly="1000" data-yearly="10000" data-two-year="20000" data-three-year="30000">1000</span><small class="price-unit">/ month</small></div>
            <a class="purchase-button" href="{{ route('register') }}">
                <svg class="cart-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M3.5 4h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.4L20.5 8H6.1M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Purchase Now</span>
                <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <ul>
                <li><strong data-monthly="50 GB" data-yearly="50 GB">50 GB</strong> NVMe storage</li>
                <li><strong data-monthly="700 GB" data-yearly="500 GB">700 GB</strong> bandwidth/month</li>
                <li><strong data-monthly="150" data-yearly="100">150</strong> FTP users</li>
                <li><strong data-monthly="10" data-yearly="10">10</strong> websites/addons</li>
                <li><strong data-monthly="300" data-yearly="100">300</strong> email accounts</li>
            </ul>
            <button class="features-toggle" type="button" aria-haspopup="dialog" aria-controls="features-dialog">See more</button>
            <ul class="extra-features" hidden>
                <li data-monthly="200 email mailing lists" data-yearly="100 email mailing lists">200 email mailing lists</li><li data-monthly="100 MySQL databases" data-yearly="50 MySQL databases">100 MySQL databases</li><li data-monthly="100 subdomains" data-yearly="100 subdomains">100 subdomains</li>                <li class="feature-unavailable" data-feature="ecommerce">eCommerce</li><li class="feature-unavailable">Remote database access</li><li class="feature-unavailable">PostgreSQL databases</li><li class="feature-unavailable">PhpPG Admin</li><li class="feature-unavailable">SSH access (Terminal)</li><li class="feature-unavailable">Shell access (Jailed)</li><li class="feature-unavailable">eAccelerator</li>
            </ul>
        </article>

        <article class="plan-card">
            <h3 class="plan-circle plan-circle-premium">Plan D</h3>
            <p class="plan-description">For larger websites.</p>
            <div class="price"><span class="currency">৳</span><span class="price-value" data-monthly="1500" data-yearly="15000" data-two-year="30000" data-three-year="45000">1500</span><small class="price-unit">/ month</small></div>
            <a class="purchase-button" href="{{ route('register') }}">
                <svg class="cart-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M3.5 4h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.4L20.5 8H6.1M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Purchase Now</span>
                <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <ul>
                <li><strong data-monthly="100 GB" data-yearly="100 GB">100 GB</strong> NVMe storage</li>
                <li><strong data-monthly="1000 GB" data-yearly="800 GB">1000 GB</strong> bandwidth/month</li>
                <li><strong data-monthly="200" data-yearly="100">200</strong> FTP users</li>
                <li><strong data-monthly="50" data-yearly="20">50</strong> websites/addons</li>
                <li><strong data-monthly="400" data-yearly="100">400</strong> email accounts</li>
            </ul>
            <button class="features-toggle" type="button" aria-haspopup="dialog" aria-controls="features-dialog">See more</button>
            <ul class="extra-features" hidden>
                <li data-monthly="300 email mailing lists" data-yearly="100 email mailing lists">300 email mailing lists</li><li data-monthly="100 MySQL databases" data-yearly="50 MySQL databases">100 MySQL databases</li><li data-monthly="100 subdomains" data-yearly="100 subdomains">100 subdomains</li>                <li class="feature-unavailable" data-feature="ecommerce">eCommerce</li><li class="feature-unavailable">Remote database access</li><li class="feature-unavailable">PostgreSQL databases</li><li class="feature-unavailable">PhpPG Admin</li><li class="feature-unavailable">SSH access (Terminal)</li><li class="feature-unavailable">Shell access (Jailed)</li><li class="feature-unavailable">eAccelerator</li>
            </ul>
        </article>

        <article class="plan-card">
            <h3 class="plan-circle plan-circle-enterprise">Plan E</h3>
            <p class="plan-description">For high-traffic websites.</p>
            <div class="price"><span class="currency">৳</span><span class="price-value" data-monthly="2500" data-yearly="25000" data-two-year="50000" data-three-year="75000">2500</span><small class="price-unit">/ month</small></div>
            <a class="purchase-button" href="{{ route('register') }}">
                <svg class="cart-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M3.5 4h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.4L20.5 8H6.1M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Purchase Now</span>
                <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <ul>
                <li><strong data-monthly="200 GB" data-yearly="200 GB">200 GB</strong> NVMe storage</li>
                <li><strong data-monthly="2000 GB" data-yearly="1000 GB">2000 GB</strong> bandwidth/month</li>
                <li><strong data-monthly="200" data-yearly="100">200</strong> FTP users</li>
                <li><strong data-monthly="100" data-yearly="50">100</strong> websites/addons</li>
                <li><strong data-monthly="500" data-yearly="200">500</strong> email accounts</li>
            </ul>
            <button class="features-toggle" type="button" aria-haspopup="dialog" aria-controls="features-dialog">See more</button>
            <ul class="extra-features" hidden>
                <li data-monthly="300 email mailing lists" data-yearly="200 email mailing lists">300 email mailing lists</li><li data-monthly="200 MySQL databases" data-yearly="100 MySQL databases">200 MySQL databases</li><li data-monthly="100 subdomains" data-yearly="100 subdomains">100 subdomains</li>                <li class="feature-unavailable" data-feature="ecommerce">eCommerce</li><li class="feature-unavailable">Remote database access</li><li class="feature-unavailable">PostgreSQL databases</li><li class="feature-unavailable">PhpPG Admin</li><li class="feature-unavailable">SSH access (Terminal)</li><li class="feature-unavailable">Shell access (Jailed)</li><li class="feature-unavailable">eAccelerator</li>
            </ul>
        </article>
    </div>
    @endif

    <div class="plans" aria-label="Shared hosting plans">
        @foreach($plans as $plan)
            <article class="plan-card{{ $plan['badge'] === 'Most popular' ? ' plan-card-featured' : '' }}">
                @if($plan['badge'])
                    <span class="{{ $plan['badge'] === 'Most popular' ? 'popular-badge' : 'cheapest-badge' }}">{{ $plan['badge'] }}</span>
                @endif
                <h3 class="plan-circle {{ $plan['class'] }}">{{ $plan['name'] }}</h3>
                <p class="plan-description">{{ $plan['description'] }}</p>
                <div class="price"><span class="currency">৳</span><span class="price-value" data-monthly="{{ $plan['prices']['monthly'] }}" data-yearly="{{ $plan['prices']['yearly'] }}" data-two-year="{{ $plan['prices']['twoYear'] }}" data-three-year="{{ $plan['prices']['threeYear'] }}">{{ $plan['prices']['monthly'] }}</span><small class="price-unit">/ month</small></div>
                <a class="purchase-button" href="{{ route('register') }}">
                    <svg class="cart-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3.5 4h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.4L20.5 8H6.1M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 0 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Purchase Now</span>
                    <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <ul>
                    @foreach($plan['primary_features'] as $feature)
                        <li><strong data-monthly="{{ $feature['monthly'] }}" data-yearly="{{ $feature['yearly'] }}">{{ $feature['monthly'] }}</strong> {{ $feature['name'] }}</li>
                    @endforeach
                </ul>
                <button class="features-toggle" type="button" aria-haspopup="dialog" aria-controls="features-dialog">See more</button>
                <ul class="extra-features" hidden>
                    @foreach($plan['extra_features'] as $feature)
                        <li data-monthly="{{ $feature['monthly'] }}" data-yearly="{{ $feature['yearly'] }}">{{ $feature['monthly'] }}</li>
                    @endforeach
                    @foreach($unavailableFeatures as $feature)
                        <li class="feature-unavailable" @if(isset($feature['feature'])) data-feature="{{ $feature['feature'] }}" @endif>{{ $feature['name'] }}</li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>

</main>

<dialog class="features-dialog" id="features-dialog">
    <div class="dialog-header">
        <div>
            <p class="dialog-eyebrow">Access control</p>
            <h2 id="dialog-title">More features</h2>
        </div>
        <button class="dialog-close" type="button" aria-label="Close feature list">×</button>
    </div>
    <div class="dialog-summary" id="dialog-summary"></div>
    <p class="dialog-acl-note">CMS controls.</p>
</dialog>

<script>
    document.querySelectorAll('.plan-card').forEach((card) => {
        const primaryFeatures = card.querySelector(':scope > ul:not(.extra-features)');
        const extraFeatures = card.querySelector(':scope > .extra-features');
        const featureGroups = document.createElement('div');
        featureGroups.className = 'feature-groups';

        const groups = [
            { title: 'Core resources', features: [...primaryFeatures.children] },
            {
                title: 'Account & databases',
                features: [...extraFeatures.children].filter((feature) => !feature.classList.contains('feature-unavailable'))
            },
            {
                title: 'Advanced & security',
                features: [...extraFeatures.children].filter((feature) => feature.classList.contains('feature-unavailable'))
            }
        ];

        groups.forEach(({ title, features }) => {
            if (!features.length) {
                return;
            }
            const group = document.createElement('section');
            group.className = 'feature-group';
            group.innerHTML = `<h4>${title}</h4>`;
            const list = document.createElement('ul');
            features.forEach((feature) => {
                if (!feature.querySelector('strong')) {
                    const match = feature.textContent.trim().match(/^(\d+(?:\s*(?:GB|TB))?)(.*)$/);
                    if (match) {
                        feature.innerHTML = `<strong>${match[1]}</strong>${match[2]}`;
                    }
                }
                list.append(feature);
            });
            group.append(list);
            featureGroups.append(group);
        });

        primaryFeatures.replaceWith(featureGroups);
        extraFeatures.remove();
        featureGroups.querySelectorAll('li').forEach((feature) => {
            feature.dataset.originalMarkup = feature.innerHTML;
            feature.dataset.originalClassName = feature.className;
        });
    });

    const periods = {
        monthly: 'Flexible month-to-month billing',
        yearly: 'Annual hosting package pricing',
        twoYear: 'Coming soon',
        threeYear: 'Coming soon'
    };

    const priceLabels = document.querySelectorAll('.price-value');
    document.querySelectorAll('.plan-card').forEach((card) => {
        const purchaseButton = card.querySelector('.purchase-button');
        purchaseButton.dataset.defaultLabel = purchaseButton.querySelector('span').textContent;
        purchaseButton.dataset.baseHref = purchaseButton.href;
    });

    const updateFeatureAvailability = (period) => {
        const comingSoon = period === 'twoYear' || period === 'threeYear';
        document.querySelectorAll('.plan-card').forEach((card) => {
            const plan = card.querySelector('h3').textContent.trim().slice(-1);
            const ecommerceAvailable = !comingSoon && (period === 'monthly' || period === 'yearly') && ['C', 'D', 'E'].includes(plan);
            card.querySelectorAll('.feature-groups li').forEach((feature) => {
                if (comingSoon) {
                    feature.classList.add('feature-unavailable');
                    feature.innerHTML = 'Coming soon';
                    return;
                }
                feature.className = feature.dataset.originalClassName;
                feature.innerHTML = feature.dataset.originalMarkup;
                if (feature.dataset.feature === 'ecommerce' && ecommerceAvailable) {
                    feature.classList.remove('feature-unavailable');
                    feature.innerHTML = 'eCommerce';
                }
            });
        });
    };

    updateFeatureAvailability('monthly');

    document.querySelectorAll('.billing-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const note = periods[tab.dataset.period];
            document.querySelectorAll('.billing-tab').forEach((item) => {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            priceLabels.forEach((price) => {
                const isComingSoon = tab.dataset.period === 'twoYear' || tab.dataset.period === 'threeYear';
                price.textContent = isComingSoon ? 'Coming soon' : price.dataset[tab.dataset.period];
            });
            document.querySelectorAll('.plan-card').forEach((card) => {
                const isComingSoon = tab.dataset.period === 'twoYear' || tab.dataset.period === 'threeYear';
                const purchaseButton = card.querySelector('.purchase-button');
                const label = purchaseButton.querySelector('span');
                card.classList.toggle('is-coming-soon', isComingSoon);
                label.textContent = isComingSoon ? 'Coming soon' : purchaseButton.dataset.defaultLabel;
                purchaseButton.setAttribute('aria-disabled', isComingSoon ? 'true' : 'false');
                purchaseButton.href = isComingSoon ? '#' : purchaseButton.dataset.baseHref;
            });
            updateFeatureAvailability(tab.dataset.period);
            const resourceLabels = document.querySelectorAll('.feature-groups strong[data-monthly]');
            const extraResourceLabels = document.querySelectorAll('.feature-groups li[data-monthly]');
            resourceLabels.forEach((resource) => {
                const resourcePeriod = tab.dataset.period === 'monthly' ? 'monthly' : 'yearly';
                resource.textContent = resource.dataset[resourcePeriod];
            });
            extraResourceLabels.forEach((resource) => {
                const resourcePeriod = tab.dataset.period === 'monthly' ? 'monthly' : 'yearly';
                const value = resource.dataset[resourcePeriod];
                const match = value.match(/^(\d+(?:\s*(?:GB|TB))?)(.*)$/);
                resource.innerHTML = match ? `<strong>${match[1]}</strong>${match[2]}` : value;
            });
            updateFeatureAvailability(tab.dataset.period);
            document.querySelectorAll('.price-unit').forEach((unit) => {
                const units = {
                    monthly: '/ month',
                    yearly: '/ year',
                    twoYear: '/ 2 years',
                    threeYear: '/ 3 years'
                };
                unit.textContent = units[tab.dataset.period];
            });
            document.querySelectorAll('.purchase-button').forEach((button) => {
                if (tab.dataset.period === 'twoYear' || tab.dataset.period === 'threeYear') {
                    return;
                }
                const url = new URL(button.dataset.baseHref, window.location.href);
                url.searchParams.set('period', tab.dataset.period);
                button.href = url.toString();
            });
            document.querySelector('#billing-note').textContent = note;
        });
    });

    document.querySelectorAll('.features-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const card = toggle.closest('.plan-card');
            const dialog = document.querySelector('#features-dialog');
            const title = card.querySelector('h3').textContent;
            const description = card.querySelector('.plan-description').textContent;
            const price = card.querySelector('.price').cloneNode(true);
            const summary = document.querySelector('#dialog-summary');
            document.querySelector('#dialog-title').textContent = `${title} access control`;
            summary.innerHTML = `<p>${description}</p><div class="dialog-price"></div>`;
            summary.querySelector('.dialog-price').append(price);
            dialog.showModal();
        });
    });

    document.querySelectorAll('.purchase-button').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (button.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
            }
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
    @include('partials.chat')
    </body>
</html>
