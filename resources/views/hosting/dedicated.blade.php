@php($cartCount = count(session('domain_cart', [])))
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Dedicated hosting with powerful resources and dependable performance.">
    <title>Dedicated Hosting | dfnhosting</title>
    <style>
:root{--navy:#101d43;--navy-deep:#0b1533;--blue:#2f73e8;--blue-soft:#eaf2ff;--ink:#18243d;--muted:#6f7b91;--paper:#f7f9fc;--line:#e2e8f2;--lime:#b8e36b}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.5 Arial,Helvetica,sans-serif}a{color:inherit;text-decoration:none}button{font:inherit}.page-width{width:min(1180px,calc(100% - 40px));margin-inline:auto}
.site-header{display:flex;align-items:center;justify-content:space-between;min-height:72px;padding:0 max(20px,calc((100% - 1180px)/2));background:var(--navy-deep);color:#dce5f7}.logo{color:#fff;font-size:1.18rem;font-weight:800;letter-spacing:-.04em}.main-nav{display:flex;align-items:center;gap:24px;font-size:.9rem}.main-nav>a,.hosting-menu summary{padding:8px 0;font-weight:600}.main-nav a:hover,.hosting-menu summary:hover,.hosting-menu[open] summary{color:var(--blue)}.hosting-menu{position:relative}.hosting-menu summary{cursor:pointer;list-style:none}.hosting-menu summary::-webkit-details-marker{display:none}.hosting-menu summary:after{content: "";margin-left:6px;font-size:.7rem}.hosting-menu-list{position:absolute;z-index:8;top:calc(100% + 12px);right:0;display:grid;gap:4px;min-width:175px;padding:8px;border:1px solid var(--line);border-radius:10px;background:#fff;box-shadow:0 14px 30px #18243d1c}.hosting-menu-list a{padding:9px 10px;border-radius:7px;color:var(--navy);font-weight:600;white-space:nowrap}.hosting-menu-list a:hover{background:var(--blue-soft)}
.page-banner{padding:48px 0 44px;background:var(--navy);color:#fff}.page-banner h1{margin:0 0 20px;font-size:clamp(2.4rem,5vw,4rem);letter-spacing:-.06em;line-height:1}.breadcrumb{display:flex;gap:9px;color:#9dadcf;font-size:.86rem}.breadcrumb strong{color:#fff;font-weight:500}
.pricing{padding:58px 0 72px}.intro-block{max-width:820px;margin:0 auto 30px;text-align:center}.eyebrow{margin:0 0 12px;color:var(--blue);font-size:.7rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase}.intro-block h2{margin:0 0 12px;font-size:clamp(2rem,4vw,3rem);line-height:1.05;letter-spacing:-.06em}.intro-block p:last-child{margin:0;color:var(--muted)}
.billing-tabs{display:flex;width:max-content;max-width:100%;margin:0 auto;padding:5px;border:1px solid #d7dfeb;border-radius:12px;background:#fff;box-shadow:0 10px 24px #18243d12;overflow-x:auto}.billing-tab{display:flex;align-items:center;justify-content:center;gap:6px;min-width:108px;padding:11px 15px;border:0;border-radius:8px;background:transparent;color:var(--muted);cursor:pointer;font-size:.88rem;font-weight:700}.billing-tab:before{width:6px;height:6px;border:1.5px solid #aeb9ca;border-radius:50%;content:""}.billing-tab.is-active{background:var(--navy);color:#fff;box-shadow:0 5px 12px #101d4330}.billing-tab.is-active:before{border-color:var(--lime);background:var(--lime)}.billing-note{min-height:24px;margin:11px 0 58px;color:var(--blue);text-align:center;font-size:.9rem;font-weight:700}
.plans{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;align-items:start}.plans .plan-card:last-child{grid-column:2 / span 2;width:calc(50% - 9px);justify-self:center}.plan-card{position:relative;display:flex;flex-direction:column;min-height:390px;padding:28px 24px 0;border:1px solid #dfe6ef;border-radius:14px;background:#fff;box-shadow:0 10px 26px #18243d12;transition:transform .2s,box-shadow .2s}.plan-card:hover{transform:translateY(-4px);box-shadow:0 16px 32px #18243d1c}.plan-circle{display:block;width:auto;height:auto;margin:0 0 16px;color:#607087;font-size:.82rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}.plan-circle-basic,.plan-circle-standard,.plan-circle-advanced,.plan-circle-premium,.plan-circle-enterprise{background:transparent}.plan-description{display:none}.price{display:flex;align-items:baseline;margin:0 0 18px;color:#101d2f;font-size:2.6rem;font-weight:800;letter-spacing:-.05em}.currency{margin-right:2px;font-size:1.6rem}.price small{margin-left:6px;color:#667085;font-size:.8rem;font-weight:400;letter-spacing:0}.purchase-button{order:5;display:flex;align-items:center;justify-content:center;min-height:44px;margin:15px 0 -22px;border:0;border-radius:8px;background:#0497ce;color:#fff;font-size:.84rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.purchase-button .cart-icon,.purchase-button .arrow{display:none}.feature-groups{order:3;max-height:245px;margin:0;padding:12px 8px 8px 0;overflow-y:auto;border-top:1px solid #dbe2eb;scrollbar-color:#9fb5d0 transparent}.feature-group+.feature-group{margin-top:16px}.feature-group h4{margin:0 0 7px;color:var(--navy);font-size:.75rem;letter-spacing:.06em;text-transform:uppercase}.feature-group ul{display:grid;gap:0;margin:0;padding:0;list-style:none}.feature-group li{min-height:30px;padding:6px 0;color:#596579;font-size:.8rem}.feature-group li:before{content: "";display:inline-grid;width:18px;height:18px;place-items:center;margin-right:7px;border-radius:50%;background:#dff4fb;color:#0497ce;font-size:.75rem}.feature-group li.feature-unavailable:before{content: "";background:#fff0f0;color:#ef5350}.feature-group li strong{color:#111827;font-weight:800}.features-toggle{order:4;display:flex;align-self:center;margin:12px auto 0;padding:7px 12px;border:1px solid #0497ce;border-radius:7px;background:#fff;color:#0589b8;cursor:pointer;font-size:.76rem;font-weight:700}.popular-badge{position:absolute;z-index:3;top:0;left:0;right:0;display:flex;justify-content:center;min-height:40px;align-items:center;border-radius:13px 13px 0 0;background:linear-gradient(100deg,#315be8,#7041ca);color:#fff;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.cheapest-badge{position:absolute;z-index:4;top:12px;right:12px;padding:4px 10px;border-radius:999px;background:#e9f7c9;color:#4f721e;font-size:.65rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.features-dialog{width:min(560px,calc(100% - 32px));padding:28px;border:0;border-radius:16px;background:#fff;box-shadow:0 24px 70px #101d4350}.features-dialog::backdrop{background:#101d4380;backdrop-filter:blur(3px)}.dialog-header{display:flex;justify-content:space-between;gap:20px}.dialog-eyebrow{margin:0 0 5px;color:var(--blue);font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.features-dialog h2{margin:0;color:var(--navy);font-size:1.4rem}.dialog-close{display:grid;width:30px;height:30px;place-items:center;border:0;border-radius:50%;background:#fff0f0;color:#ef5350;cursor:pointer;font-size:1.3rem}.dialog-summary{display:flex;justify-content:space-between;gap:20px;margin-top:18px;padding:16px 0;border-block:1px solid var(--line)}.dialog-summary p{margin:0;color:var(--muted)}.dialog-price .price{margin:0;font-size:1.8rem}.dialog-acl-note{margin:24px 0 0;padding:22px;border-top:1px solid var(--line);border-radius:12px;background:#f7f9fc;color:var(--muted);font-weight:700;text-align:center}
@media(max-width:720px){.page-width{width:min(100% - 28px,560px)}.site-header{padding-inline:14px}.main-nav{gap:14px}.billing-tabs{width:100%;overflow-x:auto}.plans{grid-template-columns:1fr;row-gap:30px}.plans .plan-card:last-child{grid-column:auto;width:auto}.plan-card{min-height:410px}}
    </style>
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
    --paper: #f5f8fc;
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
    background: var(--navy);
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

.page-banner {
    position: relative;
    overflow: hidden;
    padding: 64px 0 58px;
    background: linear-gradient(115deg, var(--navy) 0%, #1b3b76 100%);
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

.plans { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; align-items: stretch; margin-top: 0; }
.plans .plan-card:last-child { grid-column: auto; position: static; width: auto; justify-self: stretch; left: auto; }
.plan-card, .plan-card-featured {
    position: relative;
    display: flex;
    min-height: 560px;
    flex-direction: column;
    padding: 28px 24px 23px;
    border: 1px solid var(--line);
    border-radius: 18px;
    background: var(--white);
    box-shadow: 0 12px 30px rgba(20, 43, 85, .07);
    color: var(--ink);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.plan-card:hover, .plan-card-featured:hover { border-color: #b7cbe8; box-shadow: var(--shadow); transform: translateY(-5px); }
.plan-card-featured { border: 2px solid #7b91ee; box-shadow: 0 16px 36px rgba(75, 75, 232, .16); }
.plan-card h3.plan-circle {
    position: static;
    display: inline-flex;
    width: fit-content;
    height: auto;
    min-height: 34px;
    margin: 0 0 19px;
    padding: 8px 12px;
    place-items: center;
    border-radius: 8px;
    color: var(--white);
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .08em;
    line-height: 1;
    text-transform: uppercase;
    transform: none;
}
.plan-circle-basic { background: var(--blue); }
.plan-circle-standard { background: #147b96; }
.plan-circle-advanced { background: #4b4be8; }
.plan-circle-premium { background: #3e4c66; }
.plan-circle-enterprise { background: #7045a4; }
.plan-description { display: block; min-height: 26px; margin: 0 0 14px; color: var(--muted); font-size: .93rem; }
.price { display: flex; align-items: baseline; margin: 0 0 17px; color: var(--navy); font-size: 2.7rem; font-weight: 850; letter-spacing: -.07em; }
.currency { margin-right: 3px; color: var(--blue); font-size: 1.55rem; }
.price small { margin-left: 6px; color: var(--muted); font-size: .8rem; font-weight: 550; letter-spacing: 0; }
.purchase-button {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 49px;
    margin-top: 0;
    padding: 11px 16px;
    border: 0;
    border-radius: 10px;
    background: var(--blue);
    color: var(--white);
    box-shadow: 0 8px 18px rgba(37, 99, 235, .25);
    font-size: .86rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
}
.purchase-button:hover { background: var(--blue-dark); box-shadow: 0 12px 22px rgba(37, 99, 235, .3); transform: translateY(-2px); }
.purchase-button .cart-icon, .purchase-button .arrow { display: none; }
.feature-groups { order: 3; max-height: 310px; margin: 24px 0 0; padding: 18px 5px 5px 0; overflow-y: auto; border-top: 1px solid var(--line); }
.feature-group + .feature-group { margin-top: 18px; }
.feature-group h4 { margin: 0 0 8px; color: var(--navy); font-size: .73rem; letter-spacing: .08em; text-transform: uppercase; }
.feature-group ul { display: grid; gap: 0; margin: 0; padding: 0; list-style: none; }
.feature-group li { min-height: 31px; padding: 6px 0; color: var(--muted); font-size: .84rem; }
.feature-group li::before { display: inline-grid; width: 18px; height: 18px; margin-right: 8px; place-items: center; border-radius: 50%; background: #e1f6f7; color: #0796a0; content: ""; font-size: .72rem; font-weight: 800; }
.feature-group li.feature-unavailable::before { background: #fff0f0; color: #e05252; content: ""; }
.feature-group li strong { color: var(--ink); font-weight: 800; }
.features-toggle { order: 4; align-self: center; margin: 30px auto 0; padding: 8px 13px; border: 1px solid #a9bde0; border-radius: 8px; background: #f8fbff; color: var(--blue-dark); cursor: pointer; font-size: .78rem; font-weight: 750; }
.features-toggle:hover { border-color: var(--blue); background: #edf4ff; }
.popular-badge { position: absolute; top: -13px; right: 18px; padding: 6px 11px; border-radius: 999px; background: var(--lime); color: #283b12; font-size: .67rem; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
.cheapest-badge { position: absolute; top: 18px; right: 18px; padding: 5px 10px; border-radius: 999px; background: #eef9d3; color: #55731e; font-size: .65rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }

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
.dialog-category li::before { margin-right: 9px; color: #0796a0; content: ""; font-weight: 800; }
.dialog-category li.feature-unavailable::before { color: #dc5656; content: ""; }

.checkout-page { padding: 76px 0 95px; }
.checkout-header { max-width: 700px; margin: 0 auto 40px; text-align: center; }
.checkout-header h1 { margin: 0 0 14px; color: var(--navy); font-size: clamp(2.3rem, 5vw, 3.6rem); letter-spacing: -.07em; line-height: 1; }
.checkout-header > p:last-child { margin: 0; color: var(--muted); font-size: 1.05rem; }
.checkout-layout { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(280px, .75fr); gap: 24px; align-items: start; }
.checkout-panel, .order-summary { padding: 32px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); box-shadow: var(--shadow); }
.checkout-panel h2, .order-summary h2 { margin: 0 0 7px; color: var(--navy); font-size: 1.45rem; }
.checkout-muted, .summary-note { margin: 0; color: var(--muted); }
.domain-options { display: grid; gap: 11px; margin-top: 24px; }
.domain-option { display: flex; align-items: center; gap: 12px; padding: 15px; border: 1px solid var(--line); border-radius: 11px; background: var(--white); color: var(--ink); cursor: pointer; text-align: left; }
.domain-option:hover { border-color: #a9bde0; }
.domain-option.is-selected { border-color: var(--blue); background: #edf4ff; box-shadow: 0 0 0 3px rgba(37, 99, 235, .1); }
.domain-option-icon { display: grid; width: 29px; height: 29px; flex: 0 0 29px; place-items: center; border-radius: 50%; background: #dbeaff; color: var(--blue); font-weight: 800; }
.domain-option strong, .domain-option small { display: block; }
.domain-option small { margin-top: 3px; color: var(--muted); }
.domain-search { margin-top: 28px; }
.domain-search label { display: block; margin-bottom: 8px; color: var(--navy); font-size: .9rem; font-weight: 750; }
.domain-search-row { display: flex; gap: 10px; }
.domain-search-row input, .domain-search-row select { min-height: 49px; border: 1px solid #cbd8e8; border-radius: 10px; color: var(--ink); }
.domain-search-row input { min-width: 0; flex: 1; padding: 0 14px; }
.domain-search-row select { width: 112px; padding: 0 13px; background: var(--white); font-weight: 700; }
.domain-search-row button { min-height: 49px; padding: 0 18px; border: 0; border-radius: 10px; background: var(--blue); color: var(--white); cursor: pointer; font-weight: 800; }
.domain-search-row input:focus, .domain-search-row select:focus { border-color: var(--blue); outline: 3px solid rgba(37, 99, 235, .14); }
.domain-selection-note { margin: 14px 0 0; color: var(--muted); font-size: .88rem; }
.order-summary { background: var(--navy); color: var(--white); }
.order-summary h2 { color: var(--white); }
.order-summary .eyebrow { color: var(--lime); }
.order-summary dl { margin: 24px 0; border-top: 1px solid rgba(255, 255, 255, .17); }
.order-summary dl div { display: flex; justify-content: space-between; gap: 18px; padding: 14px 0; border-bottom: 1px solid rgba(255, 255, 255, .17); }
.order-summary dt { color: #b5c5df; }
.order-summary dd { margin: 0; color: var(--white); font-weight: 800; }
.order-summary .summary-price-row dd { color: var(--lime); font-size: 1.2rem; }
.order-summary .summary-note { color: #c5d2e6; font-size: .9rem; }

.home-page { min-height: calc(100vh - 76px); }
.home-hero { padding: 106px 0 112px; background: radial-gradient(circle at 85% 15%, #dceaff 0, transparent 30%), linear-gradient(135deg, #f7faff 0%, #edf4ff 55%, #fff 100%); }
.home-hero-inner { max-width: 820px; text-align: center; }
.home-hero h1 { margin: 12px auto 18px; color: var(--navy); font-size: clamp(3rem, 7vw, 5.7rem); letter-spacing: -.085em; line-height: .95; }
.home-lead { max-width: 620px; margin: 0 auto 31px; color: var(--muted); font-size: 1.12rem; line-height: 1.7; }
.home-primary-button { display: inline-flex; align-items: center; min-height: 52px; padding: 0 25px; border-radius: 10px; background: var(--blue); color: var(--white); font-weight: 800; box-shadow: 0 10px 25px rgba(37, 99, 235, .25); }
.home-primary-button:hover { background: var(--navy); transform: translateY(-2px); }
.hosting-options { padding: 84px 0 105px; }
.home-section-heading { max-width: 660px; margin: 0 auto 38px; text-align: center; }
.home-section-heading h2 { margin: 8px 0 12px; color: var(--navy); font-size: clamp(2.1rem, 4vw, 3.2rem); letter-spacing: -.07em; line-height: 1; }
.home-section-heading > p:last-child { margin: 0; color: var(--muted); }
.hosting-option-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
.hosting-option-card { display: flex; min-height: 310px; flex-direction: column; padding: 28px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); box-shadow: 0 12px 30px rgba(20, 43, 85, .07); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.hosting-option-card:hover { border-color: #b7cbe8; box-shadow: var(--shadow); transform: translateY(-5px); }
.hosting-option-card-featured { border: 2px solid #7b91ee; }
.hosting-option-icon { display: grid; width: 47px; height: 47px; place-items: center; margin-bottom: 22px; border-radius: 13px; background: #e5efff; color: var(--blue); font-size: 1.15rem; font-weight: 850; }
.hosting-option-label { margin: -43px 0 28px 61px; color: var(--blue); font-size: .7rem; font-weight: 850; letter-spacing: .1em; text-transform: uppercase; }
.hosting-option-card h3 { margin: 0 0 10px; color: var(--navy); font-size: 1.5rem; letter-spacing: -.04em; }
.hosting-option-card p:not(.hosting-option-label) { margin: 0; color: var(--muted); line-height: 1.65; }
.hosting-option-link { display: flex; justify-content: space-between; margin-top: auto; padding-top: 24px; color: var(--blue); font-size: .9rem; font-weight: 800; }
.hosting-option-link span { font-size: 1.15rem; transition: transform .2s ease; }
.hosting-option-card:hover .hosting-option-link span { transform: translateX(4px); }
.site-footer { display: flex; justify-content: space-between; padding: 25px 0; color: var(--muted); font-size: .8rem; }

@media (max-width: 860px) {
    .plans { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .checkout-layout { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .page-width { width: min(100% - 32px, 560px); }
    .site-header { min-height: 68px; padding-inline: 16px; }
    .main-nav { gap: 16px; }
    .page-banner { padding: 48px 0 42px; }
    .pricing, .checkout-page { padding: 52px 0 65px; }
    .billing-tabs { width: 100%; display: grid; grid-template-columns: repeat(2, 1fr); }
    .billing-tab { min-width: 0; padding-inline: 8px; }
    .billing-tab::before { display: none; }
    .billing-note { margin-bottom: 30px; }
    .plans, .hosting-option-grid { grid-template-columns: 1fr; }
    .plan-card, .plan-card-featured { min-height: 0; }
    .checkout-panel, .order-summary { padding: 24px 20px; }
    .domain-search-row { display: grid; }
    .domain-search-row select, .domain-search-row button { width: 100%; }
    .home-hero { padding: 78px 0 84px; }
    .home-hero h1 { font-size: clamp(3rem, 15vw, 4.3rem); }
    .hosting-options { padding: 64px 0 76px; }
    .site-footer { flex-direction: column; gap: 4px; }
}

/* VPS: circular plan markers and clean comparison panels. */
.vps-theme .plans { gap: 22px; margin-top: 78px; }
.vps-theme .plan-card,
.vps-theme .plan-card-featured {
    min-height: 590px;
    padding: 116px 28px 22px;
    border: 0;
    border-radius: 4px;
    box-shadow: 0 15px 32px rgba(20, 43, 85, .1);
}
.vps-theme .plan-card h3.plan-circle,
.vps-theme .plan-card-featured h3.plan-circle {
    position: absolute;
    top: -78px;
    left: 50%;
    display: grid;
    width: 156px;
    height: 156px;
    margin: 0;
    padding: 0;
    place-items: center;
    border-radius: 50%;
    font-size: 1.55rem;
    font-weight: 450;
    letter-spacing: 0;
    text-transform: none;
    transform: translateX(-50%);
}
.vps-theme .plan-circle-basic { background: #1768ed; }
.vps-theme .plan-circle-standard { background: #0c638f; }
.vps-theme .plan-circle-advanced { background: #303034; }
.vps-theme .plan-circle-premium { background: #3f3f43; }
.vps-theme .plan-circle-enterprise { background: #5a4778; }
.vps-theme .plan-description { display: none; }
.vps-theme .price {
    justify-content: center;
    margin-bottom: 25px;
    padding-bottom: 22px;
    border-bottom: 1px solid #d7dbe1;
    font-size: 2.7rem;
    font-weight: 350;
    letter-spacing: -.045em;
}
.vps-theme .currency { color: var(--ink); font-size: 2.15rem; font-weight: 350; }
.vps-theme .price small { margin-left: 7px; font-size: .82rem; }
.vps-theme .plan-card > ul:not(.extra-features) {
    display: grid;
    gap: 0;
    margin: 0;
    padding: 0;
    list-style: none;
}
.vps-theme .plan-card > ul:not(.extra-features) li {
    min-height: 48px;
    padding: 12px 0;
    border-bottom: 1px solid #d7dbe1;
    color: #596579;
    font-size: .88rem;
}
.vps-theme .plan-card > ul:not(.extra-features) li::before {
    display: inline;
    width: auto;
    height: auto;
    margin-right: 9px;
    background: transparent;
    color: #31953b;
    content: "";
    font-size: 1rem;
}
.vps-theme .plan-card > ul:not(.extra-features) li strong { color: #17233a; font-weight: 800; }
.vps-theme .features-toggle { display: flex; }
.vps-theme .purchase-button {
    order: 5;
    min-height: 56px;
    margin: auto -28px -22px;
    border-radius: 0 0 4px 4px;
    background: #1768ed;
    box-shadow: none;
}
.vps-theme .plan-card:nth-child(2) .purchase-button { background: #0c638f; }
.vps-theme .plan-card:nth-child(3) .purchase-button { background: #303034; }
.vps-theme .plan-card:nth-child(4) .purchase-button { background: #3f3f43; }
.vps-theme .plan-card:nth-child(5) .purchase-button { background: #5a4778; }
.vps-theme .purchase-button:hover { filter: brightness(.9); }
.vps-theme .popular-badge { top: 0; right: 0; left: 0; border-radius: 0; background: linear-gradient(100deg, #315be8, #7041ca); color: #fff; text-align: center; }
.vps-theme .cheapest-badge { top: 16px; left: 16px; right: auto; }

/* Dedicated: compact business cards with a cyan conversion action. */
.dedicated-theme .plans { gap: 20px; margin-top: 6px; }
.dedicated-theme .plan-card,
.dedicated-theme .plan-card-featured {
    min-height: 540px;
    padding: 22px 26px 0;
    border: 2px solid #e0e8f0;
    border-radius: 18px;
    box-shadow: 0 6px 0 #e8eef3, 0 14px 30px rgba(20, 43, 85, .07);
}
.dedicated-theme .plan-card:hover,
.dedicated-theme .plan-card-featured:hover { border-color: #08a1d0; box-shadow: 0 6px 0 #e1e8ed, 0 18px 34px rgba(20, 43, 85, .13); }
.dedicated-theme .plan-card h3.plan-circle,
.dedicated-theme .plan-card-featured h3.plan-circle {
    width: auto;
    min-height: 0;
    margin-bottom: 22px;
    padding: 0;
    background: transparent;
    color: #718096;
    font-size: .9rem;
    letter-spacing: .14em;
    text-transform: uppercase;
}
.dedicated-theme .plan-description { display: none; }
.dedicated-theme .price { margin-bottom: 22px; font-size: 2.7rem; }
.dedicated-theme .currency { color: var(--ink); }
.dedicated-theme .plan-card > ul:not(.extra-features) {
    display: grid;
    gap: 0;
    margin: 0;
    padding: 14px 0 0;
    border-top: 1px solid #e0e6ec;
}
.dedicated-theme .plan-card > ul:not(.extra-features) li {
    min-height: 31px;
    padding: 5px 0;
    color: #4d5b6f;
    font-size: .9rem;
}
.dedicated-theme .plan-card > ul:not(.extra-features) li::before {
    display: inline-grid;
    width: 19px;
    height: 19px;
    margin-right: 8px;
    place-items: center;
    border-radius: 50%;
    background: #dff4fb;
    color: #0394c9;
    content: "";
    font-size: .75rem;
}
.dedicated-theme .plan-card > ul:not(.extra-features) li strong { color: #142033; font-weight: 800; }
.dedicated-theme .features-toggle { margin-top: 14px; border: 2px solid #0497ce; color: #0589b8; }
.dedicated-theme .purchase-button {
    order: 5;
    min-height: 44px;
    margin: 8px -26px -2px;
    border: 0;
    border-radius: 9px 9px 14px 14px;
    background: #0497ce;
    box-shadow: none;
}
.dedicated-theme .purchase-button:hover { background: #047da9; }
.dedicated-theme .plan-card-featured {
    border-color: #0497ce;
    background: linear-gradient(180deg, #fff 0%, #f2fbfe 100%);
    box-shadow: 0 6px 0 #cde9f3, 0 22px 44px rgba(4, 151, 206, .28);
    transform: translateY(-4px);
    padding-top: 32px;
}
.dedicated-theme .plan-card-featured:hover {
    border-color: #0497ce;
    box-shadow: 0 6px 0 #bfe3f0, 0 26px 50px rgba(4, 151, 206, .35);
    transform: translateY(-6px);
}
.dedicated-theme .popular-badge {
    display: inline-block;
    position: absolute;
    top: -15px;
    right: 16px;
    left: auto;
    width: auto;
    max-width: fit-content;
    white-space: nowrap;
    z-index: 3;
    padding: 6px 12px;
    border: 2px solid #fff;
    border-radius: 999px;
    background: linear-gradient(100deg, #0497ce, #22c8f5);
    color: #fff;
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
    box-shadow: 0 8px 18px rgba(4, 151, 206, .45);
}
.dedicated-theme .plan-card-featured h3.plan-circle {
    margin-top: 4px;
}
.dedicated-theme .plans {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 24px;
}
.dedicated-theme .price {
    position: relative;
    width: max-content;
    margin: 0 auto 22px;
    font-size: 3rem;
    font-weight: 850;
    justify-content: center;
}
.dedicated-theme .currency {
    position: absolute;
    top: -0.08em;
    left: -0.82em;
    margin: 0;
    color: #0497ce;
    font-family: "Noto Sans Bengali", "Arial Unicode MS", "Segoe UI Symbol", sans-serif;
    font-size: 1.8rem;
    font-weight: 500;
    line-height: 1;
}
.dedicated-theme .plan-card > ul:not(.extra-features) li {
    font-size: 1.18rem;
    line-height: 1.65;
    padding: 8px 0;
}
.dedicated-theme .plan-card > ul:not(.extra-features) li strong,
.dedicated-theme .feature-group li strong {
    color: #101d2f;
    font-size: 1.08em;
    font-weight: 900;
}
.dedicated-theme .feature-group li {
    font-size: 1.12rem;
    line-height: 1.65;
    padding: 7px 0;
}
.dedicated-theme .feature-groups {
    flex: 1 1 auto;
    max-height: 320px;
    margin-top: 0;
    margin-bottom: 14px;
    padding-top: 10px;
    overflow-y: auto;
}
.dedicated-theme .features-toggle {
    align-self: flex-start;
    margin: auto 0 8px;
    padding: 10px 16px;
    border: 1px solid #b9d8e6;
    border-radius: 999px;
    background: #f5fbfd;
    color: #057da8;
    font-size: .9rem;
    font-weight: 800;
    letter-spacing: .01em;
    box-shadow: 0 3px 8px rgba(4, 151, 206, .1);
    transition: border-color .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.dedicated-theme .features-toggle::after {
    display: inline-block;
    margin-left: 9px;
    content: "\2192";
    transition: transform .2s ease;
}
.dedicated-theme .features-toggle:hover {
    border-color: #0497ce;
    background: #eaf8fc;
    color: #047da9;
    box-shadow: 0 6px 14px rgba(4, 151, 206, .18);
    transform: translateY(-1px);
}
.dedicated-theme .features-toggle:hover::after { transform: translateX(3px); }
@media (max-width: 1080px) {
    .dedicated-theme .plans {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 720px) {
    .dedicated-theme .plans {
        grid-template-columns: 1fr;
    }
}

/* Shared: friendly, spacious cards with a stronger featured plan. */
.shared-theme .plans { gap: 20px; margin-top: 8px; }
.shared-theme .plan-card,
.shared-theme .plan-card-featured {
    min-height: 575px;
    padding: 27px 23px 22px;
    border-top: 4px solid var(--blue);
    border-radius: 15px;
}
.shared-theme .plan-card-featured { border-color: #6969ef; background: linear-gradient(180deg, #fff 0%, #f8f8ff 100%); }
.shared-theme .plan-card h3.plan-circle { margin-bottom: 17px; }
.shared-theme .plan-description { min-height: 25px; }
.shared-theme .plan-card > ul:not(.extra-features) {
    display: grid;
    gap: 0;
    margin: 5px 0 0;
    padding: 14px 0 0;
    border-top: 1px solid var(--line);
}
.shared-theme .plan-card > ul:not(.extra-features) li { min-height: 34px; padding: 7px 0; color: var(--muted); font-size: .87rem; }
.shared-theme .plan-card > ul:not(.extra-features) li::before { margin-right: 8px; color: #0796a0; content: ""; font-weight: 800; }
.shared-theme .plan-card > ul:not(.extra-features) li strong { color: var(--ink); font-weight: 800; }
.shared-theme .purchase-button { margin-top: 4px; }

@media (max-width: 640px) {
    .vps-theme .plans { margin-top: 68px; }
    .vps-theme .plan-card, .vps-theme .plan-card-featured { min-height: 560px; padding-inline: 24px; }
    .vps-theme .purchase-button { margin-inline: -24px; }
}

    </style>
<style>.hosting-menu summary::after{content:"\25BE"}.feature-group li::before{content:"\2713"}.feature-group li.feature-unavailable::before{content:"\00D7"}.vps-theme .plan-card > ul:not(.extra-features) li::before,.shared-theme .plan-card > ul:not(.extra-features) li::before{content:"\2713"}.dedicated-theme .plan-card > ul:not(.extra-features) li::before{content:"\2713"}.domain-option-icon{font-family:Arial,sans-serif}</style>
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
        <a href="{{ route('hosting.vps') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="2"/><rect x="3" y="13.5" width="18" height="6.5" rx="2"/><path d="M7 7.2h.01M7 16.7h.01"/></svg><strong>VPS</strong></a>
        <a href="{{ route('hosting.dedicated') }}" class="is-active" aria-current="page"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2"/><rect x="10.5" y="10.5" width="3" height="3"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/></svg><strong>Dedicated</strong></a>
    </nav>
    <section class="page-banner page-banner-compact page-banner-luxe">
    <div class="page-width">
        <p class="banner-kicker">Dedicated servers</p>
        <h1>Dedicated Hosting</h1>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('landing') }}">Home</a>
            <span>/</span>
            <strong>Dedicated Hosting</strong>
        </nav>
    </div>
</section>

<main class="pricing page-width dedicated-theme">
    <div class="hosting-bar hosting-bar-edge hosting-bar-half">
    <nav aria-label="Hosting type">
        <a href="{{ route('hosting.shared') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg><strong>Shared</strong></a>
        <a href="{{ route('hosting.vps') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="2"/><rect x="3" y="13.5" width="18" height="6.5" rx="2"/><path d="M7 7.2h.01M7 16.7h.01"/></svg><strong>VPS</strong></a>
        <a href="{{ route('hosting.dedicated') }}" aria-current="page"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2"/><rect x="10.5" y="10.5" width="3" height="3"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/></svg><strong>Dedicated</strong></a>
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
.billing-note{margin:8px 0 20px !important}
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
.plan-card.is-coming-soon .price-value{font-size:1.6rem;letter-spacing:-.02em}
.plan-card.is-coming-soon .price-unit{display:none}
.plan-card.is-coming-soon .purchase-button{background:#e8edf4 !important;color:#667085 !important;box-shadow:none !important;pointer-events:none;cursor:not-allowed}
@media (max-width:640px){.hosting-bar nav{width:100%;justify-content:space-between}.hosting-bar a{flex:1;text-align:center;padding:9px 8px}}
    </style>
        <style>
.hosting-bar-half{position:static;display:flex;justify-content:flex-start;margin:0 0 24px;padding:0;background:transparent;border:0;animation:none;pointer-events:none}
.hosting-bar-half nav{display:flex;align-items:flex-start;gap:0;padding:0;border:0;background:transparent;box-shadow:none;backdrop-filter:none;pointer-events:auto}
.hosting-bar-half a{position:relative;display:inline-flex;flex-direction:column;align-items:center;gap:5px;width:120px;margin-left:-16px;padding:10px 8px 15px;overflow:hidden;text-align:center;background:linear-gradient(180deg,#ffffff 0%,#e6f6fb 100%);border:1px solid #9fb3d3;border-radius:0 0 60px 60px / 0 0 32px 32px;box-shadow:0 4px 12px rgba(16,34,71,.08);transition:transform .25s ease,filter .25s ease,box-shadow .25s ease}
.hosting-bar-half a:first-child{margin-left:0}
.hosting-bar-half .di-svg{width:18px;height:18px;color:#047da9;transition:color .25s,transform .25s}
.hosting-bar-half strong{font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#2c3a55}
.hosting-bar-half a:hover{transform:translateY(2px);filter:brightness(1.05);box-shadow:0 8px 18px rgba(4,151,206,.25)}
.hosting-bar-half a:hover .di-svg{transform:translateY(-1px)}
.hosting-bar-half a[aria-current="page"]{z-index:3;width:132px;margin-top:0;padding:12px 10px 17px;background:linear-gradient(180deg,#1fcefb 0%,#0497ce 55%,#045473 100%);border-color:#045473;box-shadow:0 10px 24px rgba(4,151,206,.5);color:#fff}
.hosting-bar-half a[aria-current="page"] .di-svg{color:#fff}
.hosting-bar-half a[aria-current="page"] strong{color:#fff}
.hosting-bar-half a[aria-current="page"]::after{content:"";position:absolute;inset:0;background:linear-gradient(115deg,transparent 32%,rgba(255,255,255,.28) 48%,transparent 62%);transform:translateX(-130%);animation:halfSheen 4.5s ease infinite}
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

    <style>
.dedicated-theme .plan-card li::before{display:inline-grid;place-items:center;vertical-align:middle;width:20px;height:20px;margin-right:9px;border-radius:50%;background:#0497ce;color:#fff;font-family:"Segoe UI Symbol","Noto Sans Symbols 2",sans-serif;font-size:.78rem;font-weight:400;box-shadow:0 4px 10px rgba(4,151,206,.35)}
.dedicated-theme .plan-card li[data-icon="check"]::before{content:"\2713"}
.dedicated-theme .plan-card li[data-icon="storage"]::before{content:"\25A4"}
.dedicated-theme .plan-card li[data-icon="transfer"]::before{content:"\21C4"}
.dedicated-theme .plan-card li[data-icon="ftp"]::before{content:"\21C5"}
.dedicated-theme .plan-card li[data-icon="website"]::before{content:"\2302"}
.dedicated-theme .plan-card li[data-icon="mail"]::before{content:"\2709"}
.dedicated-theme .plan-card li[data-icon="db"]::before{content:"\25A6"}
.dedicated-theme .plan-card li[data-icon="subdomain"]::before{content:"\2316"}
.dedicated-theme .plan-card li[data-icon="ecommerce"]::before{content:"\25C8"}
.dedicated-theme .plan-card li[data-icon="remote"]::before{content:"\21D4"}
.dedicated-theme .plan-card li[data-icon="postgres"]::before{content:"\25A9"}
.dedicated-theme .plan-card li[data-icon="phppgadmin"]::before{content:"\25A7"}
.dedicated-theme .plan-card li[data-icon="ssh"]::before{content:"\2318"}
.dedicated-theme .plan-card li[data-icon="shell"]::before{content:"\25CD"}
.dedicated-theme .plan-card li[data-icon="speed"]::before{content:"\26A1"}
.dedicated-theme .plan-card li[data-icon="cpu"]::before{content:"\2699"}
.dedicated-theme .plan-card li.feature-unavailable::before{content:"\00D7";background:#fff0f0;color:#ef5350;box-shadow:none}
    </style>
    <div class="coming-soon-panel" hidden>
        <span class="coming-soon-pill">Coming soon</span>
        <h3>Long-term billing is on its way.</h3>
        <p>2-year and 3-year plans aren't available yet. Choose monthly or yearly to get started today.</p>
        <button class="coming-soon-back" type="button">View monthly plans</button>
    </div>
    <div class="plans" aria-label="Dedicated hosting plans">
        @foreach($plans as $plan)
        <article class="plan-card{{ $plan['badge'] === 'Most popular' ? ' plan-card-featured' : '' }}{{ $plan['terms']['monthly'] ? '' : ' is-coming-soon' }}" data-visible-monthly="{{ $plan['visibility']['monthly'] ? 'true' : 'false' }}" data-visible-yearly="{{ $plan['visibility']['yearly'] ? 'true' : 'false' }}" data-visible-twoyear="{{ $plan['visibility']['twoYear'] ? 'true' : 'false' }}" data-visible-threeyear="{{ $plan['visibility']['threeYear'] ? 'true' : 'false' }}">
          @if($plan['badge'] === 'Cheapest')
          <span class="cheapest-badge">Cheapest</span>
          @elseif($plan['badge'] === 'Most popular')
          <span class="popular-badge">Most popular</span>
          @endif
          <h3 class="plan-circle {{ $plan['class'] }}" data-monthly-name="{{ $plan['names']['monthly'] }}" data-yearly-name="{{ $plan['names']['yearly'] }}" data-two-year-name="{{ $plan['names']['twoYear'] }}" data-three-year-name="{{ $plan['names']['threeYear'] }}">{{ $plan['names']['monthly'] }}</h3>
          <p class="plan-description">{{ $plan['description'] }}</p>
          <div class="price">
            <span class="currency">&#2547;</span><span class="price-value" data-monthly="{{ $plan['prices']['monthly'] }}" data-yearly="{{ $plan['prices']['yearly'] }}" data-two-year="{{ $plan['prices']['twoYear'] }}" data-three-year="{{ $plan['prices']['threeYear'] }}" data-soon-monthly="{{ $plan['terms']['monthly'] ? 'false' : 'true' }}" data-soon-yearly="{{ $plan['terms']['yearly'] ? 'false' : 'true' }}" data-soon-twoyear="{{ $plan['terms']['twoYear'] ? 'false' : 'true' }}" data-soon-threeyear="{{ $plan['terms']['threeYear'] ? 'false' : 'true' }}">{{ $plan['terms']['monthly'] ? $plan['prices']['monthly'] : 'Coming soon' }}</span><small class="price-unit">/ month</small>
          </div>
          <a class="purchase-button" href="{{ route('register') }}">
            <svg class="cart-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M3.5 4h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.4L20.5 8H6.1M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Choose this plan</span>
            <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>
          <ul>
            @foreach($plan['primary_features'] as $feature)
            <li class="{{ $feature['available'] ? '' : 'feature-unavailable' }}">
              <strong data-monthly="{{ $feature['monthly'] }}" data-yearly="{{ $feature['yearly'] }}">{{ $feature['monthly'] }}</strong>
              {{ $feature['name'] }}
            </li>
            @endforeach
          </ul>
          <button class="features-toggle" type="button" aria-haspopup="dialog" aria-controls="features-dialog">
            See more
          </button>
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

    document.querySelectorAll('.plan-card').forEach((card) => {
        const purchaseButton = card.querySelector('.purchase-button');
        purchaseButton.dataset.defaultLabel = purchaseButton.querySelector('span').textContent;
        purchaseButton.dataset.baseHref = purchaseButton.href;
    });

    // Builds the grouped feature list of one card from its two source lists
    // (primary rows + extra rows). Used for the initial render and again
    // whenever the billing tab changes — the CMS stores separate feature
    // rows per billing term (template.feature-set on each card).
    const buildDedicatedFeatureGroups = (card, primaryItems, extraItems) => {
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
    };

    document.querySelectorAll('.plan-card').forEach((card) => {
        const primaryList = card.querySelector(':scope > ul:not(.extra-features)');
        const extraList = card.querySelector(':scope > .extra-features');
        if (primaryList && extraList) {
            buildDedicatedFeatureGroups(card, [...primaryList.children], [...extraList.children]);
        }
    });

    // Tag each feature with an icon key for the themed markers.
    document.querySelectorAll('.feature-group li').forEach((item) => {
        const text = item.textContent.toLowerCase();
        let icon = 'check';
        if (text.includes('subdomain')) icon = 'subdomain';
        else if (text.includes('remote')) icon = 'remote';
        else if (text.includes('e-commerce') || text.includes('ecommerce')) icon = 'ecommerce';
        else if (text.includes('postgresql')) icon = 'postgres';
        else if (text.includes('phppgadmin')) icon = 'phppgadmin';
        else if (text.includes('ssh') || text.includes('terminal')) icon = 'ssh';
        else if (text.includes('shell') || text.includes('jailed')) icon = 'shell';
        else if (text.includes('eaccelerator')) icon = 'speed';
        else if (text.includes('nvme') || text.includes('storage') || text.includes('disk')) icon = 'storage';
        else if (text.includes('bandwidth')) icon = 'transfer';
        else if (text.includes('ftp')) icon = 'ftp';
        else if (text.includes('website') || text.includes('add-on') || text.includes('addon')) icon = 'website';
        else if (text.includes('mail')) icon = 'mail';
        else if (text.includes('mysql') || text.includes('database')) icon = 'db';
        else if (text.includes('cpu') || text.includes('core')) icon = 'cpu';
        item.dataset.icon = icon;
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
            label.textContent = soon ? 'Coming soon' : purchaseButton.dataset.defaultLabel;
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
                    buildDedicatedFeatureGroups(
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
            document.querySelectorAll('.billing-tab').forEach((item) => {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            applyPeriod(tab.dataset.period);
            // The tab is "Coming soon" only when NO visible card offers it.
            const tabSoon = tab.dataset.soon === 'true';
            document.querySelector('#billing-note').textContent = tabSoon ? 'Coming soon' : periodNotes[tab.dataset.period];
        });
    });

    document.querySelectorAll('.purchase-button').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (button.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
            }
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
    <footer class="hosting-footer">
        <div class="hosting-footer-inner">
            <a class="hf-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a>
            <span>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span>
            <span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span>
        </div>
    </footer>
    @include('partials.chat')
<script>
document.addEventListener('click',function(e){var m=document.getElementById('accountMenu');if(m&&!m.contains(e.target)){m.classList.remove('open');}});
(function(){var rail=document.querySelector('.hosting-rail');var banner=document.querySelector('.page-banner');if(!rail||!banner)return;function sync(){var past=banner.getBoundingClientRect().bottom<120;rail.classList.toggle('show',past);}document.addEventListener('scroll',sync,{passive:true});window.addEventListener('resize',sync);sync();})();
</script>
  </body>
</html>
