@php($cartCount = count(session('domain_cart', [])))
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="description"
      content="Affordable shared hosting with reliable performance for growing websites."
    />
    <title>Shared Hosting | dfnhosting</title>
    <style>
      :root {
        --navy: #101d43;
        --navy-deep: #0b1533;
        --blue: #2f73e8;
        --blue-soft: #eaf2ff;
        --ink: #18243d;
        --muted: #6f7b91;
        --paper: #f7f9fc;
        --line: #e2e8f2;
        --lime: #b8e36b;
        --gradient: linear-gradient(135deg, #2f73e8 0%, #5c3b80 100%);
      }

      * {
        box-sizing: border-box;
      }
      html {
        scroll-behavior: smooth;
      }
      body {
        margin: 0;
        background: var(--paper);
        color: var(--ink);
        font-family: Arial, Helvetica, sans-serif;
        line-height: 1.5;
      }
      a {
        color: inherit;
        text-decoration: none;
      }
      button {
        font: inherit;
      }
      .page-width {
        width: min(1280px, calc(100% - 64px));
        margin-inline: auto;
      }

      .site-header {
        position: sticky;
        top: 0;
        z-index: 100;
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
      .logo {
        color: #fff;
        font-size: 1.18rem;
        font-weight: 800;
        letter-spacing: -0.04em;
      }
      .logo span {
        color: var(--blue);
        margin: 0 3px;
      }
      .main-nav {
        display: flex;
        align-items: center;
        gap: 28px;
        color: #dce5f7;
        font-size: 0.9rem;
      }
      .main-nav > a,
      .hosting-menu summary {
        display: block;
        padding: 8px 0;
        font-weight: 600;
        transition: color 0.2s ease;
      }
      .main-nav > a:hover,
      .hosting-menu summary:hover {
        color: var(--blue);
      }
      .main-nav a:hover,
      .hosting-menu summary:hover {
        color: var(--blue);
      }
      .hosting-menu {
        position: relative;
      }
      .hosting-menu summary {
        cursor: pointer;
        list-style: none;
      }
      .hosting-menu[open] summary {
        color: var(--blue);
      }
      .hosting-menu summary::-webkit-details-marker {
        display: none;
      }
      .hosting-menu summary::after {
        display: inline-block;
        margin-left: 6px;
        content: "";
        font-size: 0.7rem;
      }
      .hosting-menu-list {
        position: absolute;
        z-index: 5;
        top: calc(100% + 14px);
        right: 0;
        display: grid;
        min-width: 175px;
        gap: 4px;
        padding: 8px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 14px 30px #18243d1c;
      }
      .hosting-menu-list a {
        padding: 9px 10px;
        border-radius: 7px;
        color: var(--navy);
        font-weight: 600;
        white-space: nowrap;
      }
      .hosting-menu-list a:hover {
        background: var(--blue-soft);
      }

      .page-banner {
        padding: 58px 0 52px;
        background: var(--navy);
        color: #fff;
      }
      .banner-kicker,
      .eyebrow {
        margin: 0 0 13px;
        color: var(--lime);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
      }
      .page-banner h1 {
        margin: 0 0 25px;
        font-size: clamp(2.5rem, 5vw, 4.3rem);
        letter-spacing: -0.06em;
        line-height: 1;
      }
      .breadcrumb {
        display: flex;
        gap: 9px;
        color: #9dadcf;
        font-size: 0.86rem;
      }
      .breadcrumb a:hover {
        color: #fff;
      }
      .breadcrumb strong {
        color: #fff;
        font-weight: 500;
      }

      .pricing {
        padding: 74px 0 55px;
      }
      .intro-block {
        max-width: 900px;
        text-align: center;
        margin: 0 auto 36px;
      }
      .intro-block .eyebrow {
        color: var(--blue);
      }
      .intro-block h2 {
        margin: 0 0 13px;
        font-size: clamp(2rem, 4vw, 3.2rem);
        line-height: 1.05;
        letter-spacing: -0.06em;
      }
      .intro-block p:last-child {
        margin: 0;
        color: var(--muted);
      }

      .billing-tabs {
        position: relative;
        display: flex;
        width: fit-content;
        margin: 0 auto 0;
        padding: 6px;
        border: 1px solid #d7dfeb;
        border-radius: 14px;
        background: #fff;
        box-shadow:
          0 12px 28px #18243d12,
          inset 0 1px #fff;
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
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        transition:
          color 0.2s ease,
          background 0.2s ease,
          box-shadow 0.2s ease;
      }
      .billing-tab::before {
        width: 6px;
        height: 6px;
        border: 1.5px solid #aeb9ca;
        border-radius: 50%;
        content: "";
        transition:
          background 0.2s ease,
          border-color 0.2s ease,
          box-shadow 0.2s ease;
      }
      .billing-tab:hover {
        color: var(--navy);
        background: #f5f8fc;
      }
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
      .billing-note {
        min-height: 24px;
        margin: 11px 0 34px;
        color: var(--blue);
        text-align: center;
        font-size: 0.9rem;
        font-weight: 700;
      }

      .plans {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        column-gap: 22px;
        row-gap: 76px;
        margin-top: 65px;
      }
      .plans .plan-card:nth-last-child(2) {
        grid-column: 2;
      }
      .plans .plan-card:last-child {
        grid-column: 3;
      }
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
        transition:
          transform 0.2s ease,
          box-shadow 0.2s ease;
      }
      .shared-theme .plan-card {
        min-height: 720px;
      }
      .plan-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 18px 35px #18243d14;
      }
      .plan-card-featured {
        border-color: var(--line);
        padding: 78px 24px 24px;
        box-shadow: 0 12px 30px #18243d08;
      }
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
        letter-spacing: 0.01em;
        transform: translateX(-50%);
      }
      .plan-circle-basic {
        background: #1476ed;
      }
      .plan-circle-standard {
        background: #075989;
      }
      .plan-circle-advanced {
        background: #283b55;
      }
      .plan-circle-premium {
        background: #252525;
      }
      .plan-circle-enterprise {
        background: #5c3b80;
      }
      .plan-description {
        min-height: 24px;
        margin: 0 0 13px;
        color: #657188;
        font-size: 1rem;
        font-weight: 500;
        line-height: 1.45;
      }
      .price {
        display: flex;
        align-items: baseline;
        margin-bottom: 16px;
        color: var(--navy);
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: -0.06em;
      }
      .currency {
        margin-right: 2px;
        color: var(--blue);
        font-size: 1.3rem;
      }
      .price small {
        margin-left: 5px;
        color: var(--muted);
        font-size: 0.9rem;
        font-weight: 400;
        letter-spacing: 0;
      }
      .plan-button {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 13px 16px;
        border: 1px solid var(--blue);
        border-radius: 10px;
        background: var(--blue);
        color: #fff;
        font-size: 0.88rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        box-shadow: 0 8px 18px #2f73e82e;
        transition:
          background 0.2s ease,
          border-color 0.2s ease,
          color 0.2s ease,
          box-shadow 0.2s ease,
          transform 0.2s ease;
      }
      .plan-button:hover {
        border-color: var(--navy);
        background: var(--navy);
        color: #fff;
        box-shadow: 0 12px 24px #101d4333;
        transform: translateY(-1px);
      }
      .plan-button:active {
        box-shadow: 0 4px 10px #101d4324;
        transform: translateY(0);
      }
      .plan-button:focus-visible {
        outline: 3px solid #2f73e855;
        outline-offset: 3px;
      }
      .plan-button span {
        font-size: 1.2rem;
        transition: transform 0.2s ease;
      }
      .plan-button:hover span {
        transform: translate(2px, -2px);
      }
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
        font-size: 0.94rem;
        font-weight: 800;
        letter-spacing: 0;
        white-space: nowrap;
        box-shadow:
          0 6px 16px #4545df3d,
          inset 0 1px #8585ff80;
        transition:
          transform 0.2s ease,
          border-color 0.2s ease,
          box-shadow 0.2s ease,
          filter 0.2s ease;
      }
      .purchase-button:hover {
        transform: translateY(-2px);
        border-color: #3434c8;
        filter: saturate(1.08);
        box-shadow:
          0 11px 22px #4545df52,
          inset 0 1px #8d8dff;
      }
      .purchase-button:active {
        transform: translateY(0);
        box-shadow:
          0 4px 10px #4545df38,
          inset 0 2px 4px #25258c40;
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
        transition: transform 0.2s ease;
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
        transition:
          transform 0.2s ease,
          background 0.2s ease;
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
          font-size: 0.88rem;
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
      .feature-group + .feature-group {
        margin-top: 22px;
      }
      .feature-group h4 {
        margin: 0 0 10px;
        color: var(--navy);
        font-size: 0.82rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
      }
      .feature-group ul {
        display: grid;
        gap: 12px;
        margin: 0;
        padding: 0;
        list-style: none;
        color: #657188;
        font-size: 0.95rem;
        font-weight: 400;
        line-height: 1.45;
      }
      .feature-group li {
        min-height: 18px;
      }
      .plan-card li::before {
        content: "\2713";
        display: inline-grid;
        width: 18px;
        height: 18px;
        place-items: center;
        margin-right: 8px;
        border-radius: 6px;
        background: #4b4be8;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        box-shadow: 0 3px 7px #4b4be82e;
      }
      .shared-theme .plan-card li.feature-unavailable {
        color: #6f7b91;
      }
      .shared-theme .plan-card li.feature-unavailable::before {
        content: "";
        background: #e8edf4;
        color: #6f7b91;
        font-size: 0.8rem;
        box-shadow: none;
      }
      .plan-card li strong {
        color: var(--navy);
        font-weight: 700;
      }
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
        font-size: 0.88rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        text-align: center;
        transition:
          border-color 0.2s ease,
          background 0.2s ease,
          color 0.2s ease,
          box-shadow 0.2s ease,
          transform 0.2s ease;
      }
      .features-toggle:hover {
        border-color: #b9cbe3;
        background: #eef4fb;
        color: var(--blue);
        box-shadow: 0 5px 12px #18243d12;
        transform: translateY(-1px);
      }
      .features-toggle:focus-visible {
        outline: 3px solid #2f73e855;
        outline-offset: 3px;
      }
      .features-toggle::after {
        display: grid;
        width: 18px;
        height: 18px;
        place-items: center;
        border-radius: 50%;
        background: var(--blue-soft);
        color: var(--blue);
        content: "+";
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1;
      }
      .feature-groups::-webkit-scrollbar {
        width: 8px;
      }
      .feature-groups::-webkit-scrollbar-thumb {
        border: 2px solid transparent;
        border-radius: 999px;
        background: #b9cbe3;
        background-clip: padding-box;
      }
      .feature-groups::-webkit-scrollbar-track {
        background: transparent;
      }
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
      .features-dialog::backdrop {
        background: #101d4380;
        backdrop-filter: blur(3px);
      }
      .dialog-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
      }
      .dialog-eyebrow {
        margin: 0 0 5px;
        color: var(--blue);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
      }
      .features-dialog h2 {
        margin: 0;
        color: var(--navy);
        font-size: 1.45rem;
        letter-spacing: -0.04em;
      }
      .dialog-close {
        display: grid;
        width: 30px;
        height: 30px;
        place-items: center;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: #fff0f0;
        color: #ef5350;
        cursor: pointer;
        font-size: 1.35rem;
        line-height: 1;
      }
      .dialog-close:hover {
        background: #ef5350;
        color: #fff;
      }
      .dialog-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-top: 18px;
        padding: 16px 0;
        border-top: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
      }
      .dialog-summary p {
        margin: 0;
        color: var(--muted);
        font-size: 0.95rem;
      }
      .dialog-price .price {
        margin: 0;
        font-size: 1.8rem;
      }
      .dialog-price .price-unit {
        font-size: 0.8rem;
      }
      .shared-theme + .features-dialog .dialog-acl-note {
        margin: 24px 0 0;
        padding: 22px;
        border-top: 1px solid var(--line);
        border-radius: 12px;
        background: #f7f9fc;
        color: var(--muted);
        font-size: 1rem;
        font-weight: 700;
        text-align: center;
      }
      .dialog-features {
        display: block;
        max-height: 530px;
        margin: 24px 0 0;
        padding: 8px 8px 8px 0;
        overflow-y: auto;
        border-top: 1px solid var(--line);
        color: var(--muted);
        font-size: 1rem;
      }
      .dialog-category + .dialog-category {
        margin-top: 22px;
      }
      .dialog-category h3 {
        margin: 0;
        padding: 0 0 8px;
        color: var(--navy);
        font-size: 1.08rem;
        letter-spacing: -0.01em;
      }
      .dialog-category ul {
        margin: 0;
        padding: 0;
        list-style: none;
      }
      .dialog-category li {
        display: flex;
        align-items: center;
        min-height: 44px;
        padding: 9px 0;
        border-bottom: 1px solid #e8edf4;
        font-weight: 400;
      }
      .dialog-features li:not(.feature-unavailable) {
        color: #6f7b91;
      }
      .dialog-features li strong {
        margin-right: 4px;
        color: #111827;
        font-weight: 700;
      }
      .dialog-features li::before {
        content: "\2713";
        display: inline-grid;
        width: 20px;
        height: 20px;
        flex: 0 0 20px;
        place-items: center;
        margin-right: 11px;
        border-radius: 6px;
        background: #4b4be8;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
      }
      .dialog-features li.feature-unavailable::before {
        content: "\00D7";
        background: #fff0f0;
        color: #ef5350;
        font-size: 0.95rem;
      }

      .trust-strip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        padding: 26px 0 45px;
        border-top: 1px solid var(--line);
        color: var(--muted);
        font-size: 0.82rem;
      }
      .trust-strip span {
        color: var(--navy);
        font-weight: 700;
      }
      .trust-strip div {
        display: flex;
        align-items: center;
        gap: 15px;
      }
      .trust-strip b {
        color: var(--blue);
      }
      .trust-strip i {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #b8c2d2;
      }
      .site-footer {
        display: flex;
        justify-content: space-between;
        padding: 25px 0;
        color: var(--muted);
        font-size: 0.78rem;
      }
      .home-page {
        min-height: calc(100vh - 150px);
      }
      .home-hero {
        padding: 104px 0 118px;
        background: linear-gradient(135deg, #f7f9ff 0%, #eef3ff 52%, #fff 100%);
      }
      .home-hero-inner {
        max-width: 820px;
        text-align: center;
      }
      .home-hero h1 {
        max-width: 720px;
        margin: 14px auto 18px;
        color: var(--navy);
        font-size: clamp(2.7rem, 6vw, 5.2rem);
        letter-spacing: -0.065em;
        line-height: 0.98;
      }
      .home-lead {
        max-width: 590px;
        margin: 0 auto 30px;
        color: var(--muted);
        font-size: 1.1rem;
        line-height: 1.7;
      }
      .home-primary-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 50px;
        padding: 0 24px;
        border-radius: 10px;
        background: var(--blue);
        color: #fff;
        font-weight: 800;
        box-shadow: 0 10px 22px #2f73e833;
        transition:
          transform 0.2s ease,
          box-shadow 0.2s ease,
          background 0.2s ease;
      }
      .home-primary-button:hover {
        background: var(--navy);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 14px 26px #101d4330;
      }
      .hosting-options {
        padding: 88px 0 100px;
      }
      .home-section-heading {
        max-width: 620px;
        margin: 0 auto 38px;
        text-align: center;
      }
      .home-section-heading h2 {
        margin: 9px 0 12px;
        color: var(--navy);
        font-size: clamp(2rem, 4vw, 3rem);
        letter-spacing: -0.05em;
      }
      .home-section-heading > p:last-child {
        margin: 0;
        color: var(--muted);
        font-size: 1rem;
        line-height: 1.6;
      }
      .hosting-option-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px;
      }
      .hosting-option-card {
        display: flex;
        min-height: 300px;
        flex-direction: column;
        padding: 30px;
        border: 1px solid var(--line);
        border-radius: 18px;
        background: #fff;
        color: var(--ink);
        box-shadow: 0 12px 30px #18243d08;
        transition:
          transform 0.2s ease,
          border-color 0.2s ease,
          box-shadow 0.2s ease;
      }
      .hosting-option-card:hover {
        border-color: #b9cbe3;
        color: var(--ink);
        transform: translateY(-5px);
        box-shadow: 0 18px 35px #18243d14;
      }
      .hosting-option-card-featured {
        border-color: #b9b9f6;
        box-shadow: 0 14px 32px #4b4be81c;
      }
      .hosting-option-icon {
        display: grid;
        width: 46px;
        height: 46px;
        place-items: center;
        margin-bottom: 22px;
        border-radius: 13px;
        background: var(--blue-soft);
        color: var(--blue);
        font-size: 1.15rem;
        font-weight: 800;
      }
      .hosting-option-card h3 {
        margin: 0 0 11px;
        color: var(--navy);
        font-size: 1.45rem;
        letter-spacing: -0.03em;
      }
      .hosting-option-card p:not(.hosting-option-label) {
        margin: 0;
        color: var(--muted);
        line-height: 1.65;
      }
      .hosting-option-label {
        margin: -41px 0 28px 60px;
        color: var(--blue);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
      }
      .hosting-option-link {
        display: flex;
        justify-content: space-between;
        margin-top: auto;
        padding-top: 24px;
        color: var(--blue);
        font-size: 0.9rem;
        font-weight: 800;
      }
      .hosting-option-link span {
        font-size: 1.15rem;
        transition: transform 0.2s ease;
      }
      .hosting-option-card:hover .hosting-option-link span {
        transform: translateX(4px);
      }

      @media (max-width: 720px) {
        .page-width {
          width: min(100% - 32px, 1280px);
        }
        .main-nav {
          gap: 18px;
        }
        .site-header {
          width: 100%;
          min-height: 68px;
          padding: 0 16px;
        }
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
          box-shadow:
            0 0 0 18px #ffffff08,
            0 0 0 36px #ffffff05;
          content: "";
        }
        .page-banner .page-width {
          position: relative;
          z-index: 1;
        }
        .pricing {
          padding: 48px 0 32px;
        }
        .intro-block {
          margin-bottom: 28px;
          padding: 23px 20px 22px;
          border: 1px solid var(--line);
          border-radius: 16px;
          background: #fff;
          box-shadow: 0 10px 25px #18243d0b;
        }
        .intro-block .eyebrow {
          margin-bottom: 10px;
        }
        .intro-block h2 {
          font-size: clamp(1.85rem, 8vw, 2.5rem);
        }
        .intro-block p:last-child {
          font-size: 0.92rem;
        }
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
        .billing-tab::before {
          display: none;
        }
        .billing-tab.is-active {
          box-shadow: 0 5px 12px #101d4330;
        }
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
        .plans {
          grid-template-columns: repeat(2, minmax(0, 1fr));
          column-gap: 18px;
          row-gap: 76px;
        }
        .plans .plan-card:nth-last-child(2),
        .plans .plan-card:last-child {
          grid-column: auto;
          width: auto;
          justify-self: stretch;
          left: auto;
        }
        .trust-strip {
          display: block;
        }
        .trust-strip div {
          flex-wrap: wrap;
          margin-top: 12px;
        }
        .site-footer {
          flex-direction: column;
          gap: 5px;
        }
        .home-hero {
          padding: 76px 0 82px;
        }
        .home-hero h1 {
          font-size: clamp(2.7rem, 12vw, 4rem);
        }
        .hosting-options {
          padding: 64px 0 76px;
        }
        .hosting-option-grid {
          grid-template-columns: 1fr;
        }
      }

      @media (min-width: 721px) and (max-width: 1000px) {
        .plans {
          grid-template-columns: repeat(4, minmax(0, 1fr));
          column-gap: 14px;
        }
      }

      @media (max-width: 430px) {
        .page-width {
          width: min(100% - 24px, 1280px);
        }
        .site-header {
          min-height: 64px;
        }
        .page-banner {
          padding: 38px 0 34px;
        }
        .page-banner h1 {
          margin-bottom: 18px;
          font-size: 2.35rem;
        }
        .breadcrumb {
          font-size: 0.78rem;
        }
        .intro-block {
          padding: 21px 17px 20px;
        }
        .intro-block h2 {
          font-size: 2rem;
        }
        .billing-tabs {
          display: grid;
          grid-template-columns: repeat(2, minmax(0, 1fr));
          gap: 4px;
          padding: 5px;
        }
        .billing-tab {
          width: 100%;
        }
        .billing-note {
          max-width: 100%;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .billing-note {
          margin-bottom: 14px;
          font-size: 0.76rem;
        }
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
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        transform: none;
      }
      .plan-card .plan-circle-standard {
        background: #075989;
      }
      .plan-card .plan-circle-advanced {
        background: var(--navy);
      }
      .plan-card .plan-circle-premium {
        background: #3f4a61;
      }
      .plan-card .plan-circle-enterprise {
        background: #5c3b80;
      }
      .plans {
        margin-top: 45px;
        row-gap: 34px;
      }

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
        font-size: 0.72rem;
        letter-spacing: 0.12em;
      }
      .vps-theme .plan-description {
        min-height: 24px;
        color: #aebbd3;
        font-size: 0.92rem;
      }
      .vps-theme .price {
        margin-bottom: 18px;
        color: #fff;
      }
      .vps-theme .currency {
        color: #7db0ff;
      }
      .vps-theme .price small {
        color: #9aa9c3;
      }
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
      .vps-theme .plan-circle-basic {
        background: #1768ed;
      }
      .vps-theme .plan-circle-standard {
        background: #075589;
      }
      .vps-theme .plan-circle-advanced {
        background: #2d2d2f;
      }
      .vps-theme .plan-circle-premium {
        background: #4a4a4d;
      }
      .vps-theme .plan-circle-enterprise {
        background: #5c3b80;
      }
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
        font-size: 0.48rem;
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
        font-size: 0.62rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
      }
      .vps-theme .purchase-button > span {
        font-size: 0.62rem;
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
        font-size: 0.7rem;
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
        font-size: 0.95rem;
        box-shadow: none;
      }
      .vps-theme
        .plan-card
        > ul:not(.extra-features)
        li.feature-unavailable::before {
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
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
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
        grid-column: auto;
        width: auto;
        justify-self: stretch;
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
      .vps-theme .plan-circle-basic {
        background: #1168ee !important;
      }
      .vps-theme .plan-circle-standard {
        background: #07598a !important;
      }
      .vps-theme .plan-circle-advanced {
        background: #333335 !important;
      }
      .vps-theme .plan-circle-premium {
        background: #3a3a3c !important;
      }
      .vps-theme .plan-circle-enterprise {
        background: #51416e !important;
      }
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
        letter-spacing: -0.04em;
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
        font-size: 0.82rem;
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
        font-size: 0.96rem;
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
      .vps-theme
        .plan-card
        > ul:not(.extra-features)
        li.feature-unavailable::before {
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
        font-size: 0.9rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
      }
      .vps-theme .plan-card:nth-child(2) .purchase-button {
        background: #07598a;
      }
      .vps-theme .plan-card:nth-child(3) .purchase-button {
        background: #333335;
      }
      .vps-theme .plan-card:nth-child(4) .purchase-button {
        background: #3a3a3c;
      }
      .vps-theme .plan-card:nth-child(5) .purchase-button {
        background: #51416e;
      }
      .vps-theme .purchase-button > span {
        font-size: 0.9rem;
      }
      .vps-theme .purchase-button .cart-icon,
      .vps-theme .purchase-button .arrow {
        display: none;
      }
      .vps-theme .purchase-button:hover {
        filter: brightness(0.9);
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
        font-size: 0.78rem;
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
      .vps-theme
        ~ .features-dialog
        .dialog-features
        li.feature-unavailable::before {
        background: transparent;
        color: #ff3e4b;
        font-size: 1rem;
      }
      .vps-theme ~ .features-dialog .dialog-features li strong {
        color: #111827;
        font-weight: 800;
      }
      .vps-theme
        ~ .features-dialog
        .dialog-features
        li:not(.feature-unavailable) {
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
        box-shadow:
          0 5px 0 #e6ebef,
          0 14px 25px #18243d0b;
        color: var(--ink);
      }
      .dedicated-theme .plan-card:hover {
        border-color: #0497ce;
        transform: translateY(-3px);
        box-shadow:
          0 5px 0 #e1e8ed,
          0 18px 30px #18243d14;
      }
      .dedicated-theme .plan-card h3.plan-circle,
      .dedicated-theme .plan-card-featured h3.plan-circle {
        position: static;
        display: block;
        width: auto;
        height: auto;
        margin: 0 0 18px;
        color: #718096;
        font-size: 0.9rem;
        font-weight: 800;
        letter-spacing: 0.14em;
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
        letter-spacing: -0.05em;
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
        font-size: 0.82rem;
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
        font-size: 0.96rem;
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
        font-size: 0.82rem;
        box-shadow: none;
      }
      .dedicated-theme
        .plan-card
        > ul:not(.extra-features)
        li.feature-unavailable::before {
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
        font-size: 0.88rem;
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
        font-size: 0.9rem;
        font-weight: 800;
      }
      .dedicated-theme .plan-card-featured {
        border-color: #e2e8ef;
        box-shadow:
          0 5px 0 #e6ebef,
          0 14px 25px #18243d0b;
      }
      .dedicated-theme .plan-card-featured .purchase-button {
        border-color: #0497ce;
        background: #0497ce;
        color: #fff;
      }
      .dedicated-theme .purchase-button > span {
        font-size: 0.9rem;
      }
      .dedicated-theme .purchase-button .cart-icon,
      .dedicated-theme .purchase-button .arrow {
        display: none;
      }

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
        letter-spacing: -0.06em;
      }
      .checkout-header > p:last-child {
        margin: 0;
        color: var(--muted);
        font-size: 1.05rem;
      }
      .checkout-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.8fr);
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
        font-size: 0.9rem;
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
        transition:
          border-color 0.2s ease,
          box-shadow 0.2s ease,
          background-color 0.2s ease;
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
        font-size: 0.88rem;
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
      .order-summary dt {
        color: #aebbd3;
      }
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
      .order-summary .summary-note {
        color: #c2cee1;
        font-size: 0.9rem;
      }

      @media (max-width: 720px) {
        .checkout-page {
          padding: 48px 0 60px;
        }
        .checkout-layout {
          grid-template-columns: 1fr;
        }
        .checkout-panel,
        .order-summary {
          padding: 24px 20px;
        }
        .domain-search-row {
          display: grid;
        }
        .domain-search-row select,
        .domain-search-row button {
          width: 100%;
        }
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
        font-size: 0.78rem;
      }
      .dedicated-theme
        ~ .features-dialog
        .dialog-features
        li.feature-unavailable::before {
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
        transition:
          border-color 0.2s ease,
          box-shadow 0.2s ease,
          transform 0.2s ease;
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
      .shared-theme .plan-card-featured:hover {
        border-color: #8fa6ed;
      }
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
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        line-height: 1;
        text-transform: uppercase;
        transform: none;
      }
      .shared-theme .plan-circle-basic {
        background: #2f73e8;
      }
      .shared-theme .plan-circle-standard {
        background: #19708d;
      }
      .shared-theme .plan-circle-advanced {
        background: #4b4be8;
      }
      .shared-theme .plan-circle-premium {
        background: #3d4859;
      }
      .shared-theme .plan-circle-enterprise {
        background: #69449b;
      }
      .shared-theme .popular-badge {
        position: absolute;
        top: -13px;
        right: 18px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #b8e36b;
        color: #20300d;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
      }
      .shared-theme .plan-description {
        min-height: 24px;
        margin: 0 0 12px;
        color: #667085;
        font-size: 0.94rem;
      }
      .shared-theme .price {
        margin-bottom: 16px;
        color: #101d43;
        font-size: 2.35rem;
      }
      .shared-theme .currency {
        color: #2f73e8;
      }
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
      .shared-theme .feature-group + .feature-group {
        margin-top: 18px;
      }
      .shared-theme .feature-group h4 {
        margin-bottom: 8px;
        color: #344054;
        font-size: 0.72rem;
        letter-spacing: 0.1em;
      }
      .shared-theme .feature-group ul {
        gap: 10px;
        font-size: 0.9rem;
      }
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
        font-size: 0.82rem;
      }
      .shared-theme .features-toggle:hover {
        border-color: #2f73e8;
        background: #f7faff;
        color: #2f73e8;
        box-shadow: none;
      }
      @media (max-width: 720px) {
        .shared-theme .plans {
          row-gap: 34px;
          margin-top: 32px;
        }
        .shared-theme .plan-card,
        .shared-theme .plan-card-featured {
          min-height: 660px;
        }
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
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.1em;
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
        letter-spacing: -0.03em;
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
        letter-spacing: -0.02em;
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
        font-size: 0.8rem;
      }
      .shared-theme .feature-group ul {
        font-size: 1rem;
        line-height: 1.5;
      }
      .shared-theme .features-toggle {
        font-size: 0.9rem;
      }
      .shared-theme .plans {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        column-gap: 24px;
      }
      .shared-theme .plans .plan-card:nth-last-child(2),
      .shared-theme .plans .plan-card:last-child {
        grid-column: auto;
        width: auto;
        justify-self: stretch;
        left: auto;
      }
      .shared-theme .price {
        position: relative;
        width: max-content;
        margin-inline: auto;
        font-size: 3rem;
      }
      .shared-theme .currency {
        position: absolute;
        top: -0.08em;
        left: -0.82em;
        margin: 0;
        color: #3fc8be;
        font-family:
          "Noto Sans Bengali", "Arial Unicode MS", "Segoe UI Symbol", sans-serif;
        font-size: 1.8rem;
        font-weight: 500;
        line-height: 1;
      }
      .shared-theme .plan-card li.feature-unavailable {
        color: #6f7b91;
      }
      .shared-theme .plan-card li.feature-unavailable::before {
        display: inline-grid;
        place-items: center;
        vertical-align: middle;
        content: "\00D7";
        background: #fff1f1;
        color: #e53935;
        font-size: 0.95rem;
        font-weight: 800;
      }
      .shared-theme .plan-card h3.plan-circle {
        font-size: 1.8rem;
      }
      .shared-theme .plan-description {
        font-size: 1.1rem;
      }
      .shared-theme .feature-group h4 {
        font-size: 0.88rem;
      }
      .shared-theme .feature-group li {
        font-size: 1rem;
      }
      @media (max-width: 1080px) {
        .shared-theme .plans {
          grid-template-columns: repeat(2, minmax(0, 1fr));
        }
      }
      @media (max-width: 720px) {
        .shared-theme .plans {
          grid-template-columns: 1fr;
        }
      }
      .shared-theme .cheapest-badge,
      .shared-theme .popular-badge {
        font-size: 0.72rem;
      }
      /* Simple premium hosting plan presentation. */
      .page-width {
        width: min(1180px, calc(100% - 40px));
      }
      .site-header {
        min-height: 72px;
        padding-inline: max(20px, calc((100% - 1180px) / 2));
      }
      .page-banner {
        padding: 48px 0 44px;
      }
      .pricing {
        padding: 58px 0 72px;
      }
      .intro-block {
        margin-bottom: 30px;
      }
      .plans {
        column-gap: 18px;
        row-gap: 64px;
      }
      .shared-theme .plan-card,
      .shared-theme .plan-card-featured {
        min-height: 560px;
        padding: 126px 30px 0;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 10px 26px #18243d12;
      }
      .shared-theme .plan-card:hover,
      .shared-theme .plan-card-featured:hover {
        border-color: #2f73e8;
        transform: translateY(-4px);
        box-shadow: 0 16px 32px #18243d1c;
      }
      .shared-theme .feature-groups {
        scrollbar-color: #9fb5d0 transparent;
      }
      .shared-theme .cheapest-badge,
      .shared-theme .popular-badge {
        z-index: 2;
        padding: 7px 14px;
        border: 2px solid #fff;
        font-size: 0.76rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        box-shadow: 0 6px 14px rgba(20, 43, 85, 0.2);
      }
      .shared-theme .cheapest-badge {
        background: #dfff79;
        color: #35510b;
      }
      .shared-theme .popular-badge {
        background: linear-gradient(100deg, #315be8, #7041ca);
        color: #fff;
      }
      @media (max-width: 720px) {
        .page-width {
          width: min(100% - 28px, 560px);
        }
        .site-header {
          padding-inline: 14px;
        }
        .main-nav {
          gap: 14px;
        }
        .billing-tabs {
          max-width: 100%;
          overflow-x: auto;
        }
        .shared-theme .plans {
          grid-template-columns: 1fr;
          row-gap: 34px;
        }
        .shared-theme .plans .plan-card:nth-last-child(2),
        .shared-theme .plans .plan-card:last-child {
          grid-column: auto;
          width: auto;
          justify-self: stretch;
          left: auto;
        }
        .shared-theme .plan-card,
        .shared-theme .plan-card-featured {
          min-height: 560px;
        }
      }
      .shared-theme .plan-card h3.plan-circle {
        font-size: 1.95rem;
      }
      .shared-theme .plan-description {
        font-size: 1.15rem;
      }
      .shared-theme .price {
        font-size: 3.1rem;
      }
      .shared-theme .currency {
        top: 0.12em;
        left: -0.72em;
        font-size: 1.9rem;
      }
      .shared-theme .feature-group h4 {
        font-size: 0.94rem;
      }
      .shared-theme .feature-group li {
        font-size: 1.16rem;
        line-height: 1.55;
      }
      .shared-theme .plan-card li.feature-unavailable::before {
        display: inline-grid;
        width: 18px;
        height: 18px;
        margin-right: 8px;
        place-items: center;
        border-radius: 6px;
        background: #fff0f0;
        color: #ef5350;
        content: "\00D7" !important;
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 1;
        box-shadow: none;
        vertical-align: middle;
      }
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
        --shadow: 0 18px 50px rgba(20, 43, 85, 0.1);
      }

      * {
        box-sizing: border-box;
      }
      html {
        scroll-behavior: smooth;
      }
      body {
        margin: 0;
        background: var(--paper);
        color: var(--ink);
        font:
          16px/1.55 Inter,
          ui-sans-serif,
          system-ui,
          -apple-system,
          BlinkMacSystemFont,
          "Segoe UI",
          sans-serif;
      }
      a {
        color: inherit;
        text-decoration: none;
      }
      button,
      input,
      select {
        font: inherit;
      }
      button,
      a {
        -webkit-tap-highlight-color: transparent;
      }
      .page-width {
        width: min(1160px, calc(100% - 48px));
        margin-inline: auto;
      }

      .site-header {
        position: relative;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        max-width: none;
        min-height: 76px;
        padding: 0 max(24px, calc((100% - 1160px) / 2));
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        background: var(--navy);
        color: #dce7fb;
      }
      .logo {
        color: var(--white);
        font-size: 1.25rem;
        font-weight: 850;
        letter-spacing: -0.055em;
      }
      .logo::after {
        display: inline-block;
        width: 7px;
        height: 7px;
        margin: 0 0 2px 4px;
        border-radius: 50%;
        background: var(--lime);
        content: "";
      }
      .main-nav {
        display: flex;
        align-items: center;
        gap: 30px;
        font-size: 0.9rem;
      }
      .main-nav > a,
      .hosting-menu summary {
        display: block;
        padding: 9px 0;
        color: #dce7fb;
        cursor: pointer;
        font-weight: 650;
        transition: color 0.2s ease;
      }
      .main-nav > a:hover,
      .hosting-menu summary:hover,
      .hosting-menu[open] summary {
        color: var(--lime);
      }
      .hosting-menu {
        position: relative;
      }
      .hosting-menu summary {
        list-style: none;
      }
      .hosting-menu summary::-webkit-details-marker {
        display: none;
      }
      .hosting-menu summary::after {
        margin-left: 7px;
        color: #91a6cd;
        content: "";
        font-size: 0.8rem;
      }
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
      .hosting-menu-list a {
        padding: 10px 12px;
        border-radius: 8px;
        color: var(--ink);
        font-weight: 650;
      }
      .hosting-menu-list a:hover {
        background: #edf4ff;
        color: var(--blue);
      }

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
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 50%;
        box-shadow:
          0 0 0 35px rgba(255, 255, 255, 0.035),
          0 0 0 70px rgba(255, 255, 255, 0.025);
        content: "";
      }
      .page-banner .page-width {
        position: relative;
        z-index: 1;
      }
      .page-banner h1 {
        margin: 0 0 18px;
        font-size: clamp(2.7rem, 6vw, 4.5rem);
        letter-spacing: -0.075em;
        line-height: 0.98;
      }
      .banner-kicker,
      .eyebrow {
        margin: 0 0 13px;
        color: var(--lime);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
      }
      .breadcrumb {
        display: flex;
        gap: 10px;
        color: #aec0df;
        font-size: 0.9rem;
      }
      .breadcrumb strong {
        color: var(--white);
        font-weight: 650;
      }
      .breadcrumb a:hover {
        color: var(--lime);
      }

      .pricing {
        padding: 76px 0 90px;
      }
      .intro-block {
        max-width: 780px;
        margin: 0 auto 38px;
        text-align: center;
      }
      .intro-block .eyebrow {
        color: var(--blue);
      }
      .intro-block h2 {
        margin: 0 0 15px;
        color: var(--ink);
        font-size: clamp(2rem, 4vw, 3.35rem);
        letter-spacing: -0.07em;
        line-height: 1.05;
      }
      .intro-block p:last-child {
        margin: 0;
        color: var(--muted);
        font-size: 1.05rem;
      }
      .billing-tabs {
        display: flex;
        width: fit-content;
        max-width: 100%;
        margin: 0 auto;
        padding: 5px;
        border: 1px solid var(--line);
        border-radius: 14px;
        background: var(--white);
        box-shadow: 0 10px 28px rgba(25, 52, 96, 0.08);
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
        font-size: 0.9rem;
        font-weight: 750;
      }
      .billing-tab::before {
        width: 7px;
        height: 7px;
        border: 1.5px solid #a9b9d0;
        border-radius: 50%;
        content: "";
      }
      .billing-tab:hover {
        color: var(--blue);
        background: #f1f6ff;
      }
      .billing-tab.is-active {
        background: var(--navy);
        color: var(--white);
        box-shadow: 0 7px 15px rgba(16, 34, 71, 0.2);
      }
      .billing-tab.is-active::before {
        border-color: var(--lime);
        background: var(--lime);
      }
      .billing-tab:focus-visible,
      button:focus-visible,
      a:focus-visible {
        outline: 3px solid rgba(37, 99, 235, 0.35);
        outline-offset: 3px;
      }
      .billing-note {
        min-height: 25px;
        margin: 13px 0 42px;
        color: var(--blue);
        text-align: center;
        font-size: 0.9rem;
        font-weight: 750;
      }

      .plans {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
        align-items: stretch;
        margin-top: 0;
      }
      .plans .plan-card:last-child {
        grid-column: auto;
        position: static;
        width: auto;
        justify-self: stretch;
        left: auto;
      }
      .plan-card,
      .plan-card-featured {
        position: relative;
        display: flex;
        min-height: 560px;
        flex-direction: column;
        padding: 28px 24px 23px;
        border: 1px solid var(--line);
        border-radius: 18px;
        background: var(--white);
        box-shadow: 0 12px 30px rgba(20, 43, 85, 0.07);
        color: var(--ink);
        transition:
          transform 0.2s ease,
          box-shadow 0.2s ease,
          border-color 0.2s ease;
      }
      .plan-card:hover,
      .plan-card-featured:hover {
        border-color: #b7cbe8;
        box-shadow: var(--shadow);
        transform: translateY(-5px);
      }
      .plan-card-featured {
        border: 2px solid #7b91ee;
        box-shadow: 0 16px 36px rgba(75, 75, 232, 0.16);
      }
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
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        line-height: 1;
        text-transform: uppercase;
        transform: none;
      }
      .plan-circle-basic {
        background: var(--blue);
      }
      .plan-circle-standard {
        background: #147b96;
      }
      .plan-circle-advanced {
        background: #4b4be8;
      }
      .plan-circle-premium {
        background: #3e4c66;
      }
      .plan-circle-enterprise {
        background: #7045a4;
      }
      .plan-description {
        display: block;
        min-height: 26px;
        margin: 0 0 14px;
        color: var(--muted);
        font-size: 0.93rem;
      }
      .price {
        display: flex;
        align-items: baseline;
        margin: 0 0 17px;
        color: var(--navy);
        font-size: 2.7rem;
        font-weight: 850;
        letter-spacing: -0.07em;
      }
      .currency {
        margin-right: 3px;
        color: var(--blue);
        font-size: 1.55rem;
      }
      .price small {
        margin-left: 6px;
        color: var(--muted);
        font-size: 0.8rem;
        font-weight: 550;
        letter-spacing: 0;
      }
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
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
        font-size: 0.86rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        transition:
          background 0.2s ease,
          transform 0.2s ease,
          box-shadow 0.2s ease;
      }
      .purchase-button:hover {
        background: var(--blue-dark);
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.3);
        transform: translateY(-2px);
      }
      .purchase-button .cart-icon,
      .purchase-button .arrow {
        display: none;
      }
      .feature-groups {
        order: 3;
        max-height: 310px;
        margin: 24px 0 0;
        padding: 18px 5px 5px 0;
        overflow-y: auto;
        border-top: 1px solid var(--line);
      }
      .feature-group + .feature-group {
        margin-top: 18px;
      }
      .feature-group h4 {
        margin: 0 0 8px;
        color: var(--navy);
        font-size: 0.73rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
      }
      .feature-group ul {
        display: grid;
        gap: 0;
        margin: 0;
        padding: 0;
        list-style: none;
      }
      .feature-group li {
        min-height: 31px;
        padding: 6px 0;
        color: var(--muted);
        font-size: 0.84rem;
      }
      .feature-group li::before {
        display: inline-grid;
        width: 18px;
        height: 18px;
        margin-right: 8px;
        place-items: center;
        border-radius: 50%;
        background: #e1f6f7;
        color: #0796a0;
        content: "";
        font-size: 0.72rem;
        font-weight: 800;
      }
      .feature-group li.feature-unavailable::before {
        background: #fff0f0;
        color: #e05252;
        content: "";
      }
      .feature-group li strong {
        color: var(--ink);
        font-weight: 800;
      }
      .features-toggle {
        order: 4;
        align-self: center;
        margin: 15px auto 0;
        padding: 8px 13px;
        border: 1px solid #a9bde0;
        border-radius: 8px;
        background: #f8fbff;
        color: var(--blue-dark);
        cursor: pointer;
        font-size: 0.78rem;
        font-weight: 750;
      }
      .features-toggle:hover {
        border-color: var(--blue);
        background: #edf4ff;
      }
      .popular-badge {
        position: absolute;
        top: -13px;
        right: 18px;
        padding: 6px 11px;
        border-radius: 999px;
        background: var(--lime);
        color: #283b12;
        font-size: 0.67rem;
        font-weight: 850;
        letter-spacing: 0.06em;
        text-transform: uppercase;
      }
      .cheapest-badge {
        position: absolute;
        top: 18px;
        right: 18px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #eef9d3;
        color: #55731e;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
      }

      .features-dialog {
        width: min(560px, calc(100% - 32px));
        max-height: calc(100vh - 40px);
        padding: 28px;
        border: 0;
        border-radius: 18px;
        background: var(--white);
        color: var(--ink);
        box-shadow: 0 24px 70px rgba(16, 34, 71, 0.3);
      }
      .features-dialog::backdrop {
        background: rgba(16, 34, 71, 0.64);
        backdrop-filter: blur(4px);
      }
      .dialog-header,
      .dialog-summary {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
      }
      .dialog-eyebrow {
        margin: 0 0 5px;
        color: var(--blue);
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
      }
      .features-dialog h2 {
        margin: 0;
        color: var(--navy);
        font-size: 1.4rem;
      }
      .dialog-close {
        display: grid;
        width: 31px;
        height: 31px;
        place-items: center;
        border: 0;
        border-radius: 50%;
        background: #fff0f0;
        color: #dc5656;
        cursor: pointer;
        font-size: 1.3rem;
      }
      .dialog-summary {
        margin-top: 18px;
        padding: 16px 0;
        border-block: 1px solid var(--line);
      }
      .dialog-summary p {
        margin: 0;
        color: var(--muted);
      }
      .dialog-features {
        max-height: 480px;
        margin-top: 22px;
        overflow-y: auto;
      }
      .dialog-category + .dialog-category {
        margin-top: 20px;
      }
      .dialog-category h3 {
        margin: 0 0 7px;
        color: var(--navy);
        font-size: 1rem;
      }
      .dialog-category ul {
        margin: 0;
        padding: 0;
        list-style: none;
      }
      .dialog-category li {
        padding: 9px 0;
        border-bottom: 1px solid #e9eef5;
        color: var(--muted);
      }
      .dialog-category li::before {
        margin-right: 9px;
        color: #0796a0;
        content: "";
        font-weight: 800;
      }
      .dialog-category li.feature-unavailable::before {
        color: #dc5656;
        content: "";
      }

      .checkout-page {
        padding: 76px 0 95px;
      }
      .checkout-header {
        max-width: 700px;
        margin: 0 auto 40px;
        text-align: center;
      }
      .checkout-header h1 {
        margin: 0 0 14px;
        color: var(--navy);
        font-size: clamp(2.3rem, 5vw, 3.6rem);
        letter-spacing: -0.07em;
        line-height: 1;
      }
      .checkout-header > p:last-child {
        margin: 0;
        color: var(--muted);
        font-size: 1.05rem;
      }
      .checkout-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.75fr);
        gap: 24px;
        align-items: start;
      }
      .checkout-panel,
      .order-summary {
        padding: 32px;
        border: 1px solid var(--line);
        border-radius: 18px;
        background: var(--white);
        box-shadow: var(--shadow);
      }
      .checkout-panel h2,
      .order-summary h2 {
        margin: 0 0 7px;
        color: var(--navy);
        font-size: 1.45rem;
      }
      .checkout-muted,
      .summary-note {
        margin: 0;
        color: var(--muted);
      }
      .domain-options {
        display: grid;
        gap: 11px;
        margin-top: 24px;
      }
      .domain-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        border: 1px solid var(--line);
        border-radius: 11px;
        background: var(--white);
        color: var(--ink);
        cursor: pointer;
        text-align: left;
      }
      .domain-option:hover {
        border-color: #a9bde0;
      }
      .domain-option.is-selected {
        border-color: var(--blue);
        background: #edf4ff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
      }
      .domain-option-icon {
        display: grid;
        width: 29px;
        height: 29px;
        flex: 0 0 29px;
        place-items: center;
        border-radius: 50%;
        background: #dbeaff;
        color: var(--blue);
        font-weight: 800;
      }
      .domain-option strong,
      .domain-option small {
        display: block;
      }
      .domain-option small {
        margin-top: 3px;
        color: var(--muted);
      }
      .domain-search {
        margin-top: 28px;
      }
      .domain-search label {
        display: block;
        margin-bottom: 8px;
        color: var(--navy);
        font-size: 0.9rem;
        font-weight: 750;
      }
      .domain-search-row {
        display: flex;
        gap: 10px;
      }
      .domain-search-row input,
      .domain-search-row select {
        min-height: 49px;
        border: 1px solid #cbd8e8;
        border-radius: 10px;
        color: var(--ink);
      }
      .domain-search-row input {
        min-width: 0;
        flex: 1;
        padding: 0 14px;
      }
      .domain-search-row select {
        width: 112px;
        padding: 0 13px;
        background: var(--white);
        font-weight: 700;
      }
      .domain-search-row button {
        min-height: 49px;
        padding: 0 18px;
        border: 0;
        border-radius: 10px;
        background: var(--blue);
        color: var(--white);
        cursor: pointer;
        font-weight: 800;
      }
      .domain-search-row input:focus,
      .domain-search-row select:focus {
        border-color: var(--blue);
        outline: 3px solid rgba(37, 99, 235, 0.14);
      }
      .domain-selection-note {
        margin: 14px 0 0;
        color: var(--muted);
        font-size: 0.88rem;
      }
      .order-summary {
        background: var(--navy);
        color: var(--white);
      }
      .order-summary h2 {
        color: var(--white);
      }
      .order-summary .eyebrow {
        color: var(--lime);
      }
      .order-summary dl {
        margin: 24px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.17);
      }
      .order-summary dl div {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        padding: 14px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.17);
      }
      .order-summary dt {
        color: #b5c5df;
      }
      .order-summary dd {
        margin: 0;
        color: var(--white);
        font-weight: 800;
      }
      .order-summary .summary-price-row dd {
        color: var(--lime);
        font-size: 1.2rem;
      }
      .order-summary .summary-note {
        color: #c5d2e6;
        font-size: 0.9rem;
      }

      .home-page {
        min-height: calc(100vh - 76px);
      }
      .home-hero {
        padding: 106px 0 112px;
        background:
          radial-gradient(circle at 85% 15%, #dceaff 0, transparent 30%),
          linear-gradient(135deg, #f7faff 0%, #edf4ff 55%, #fff 100%);
      }
      .home-hero-inner {
        max-width: 820px;
        text-align: center;
      }
      .home-hero h1 {
        margin: 12px auto 18px;
        color: var(--navy);
        font-size: clamp(3rem, 7vw, 5.7rem);
        letter-spacing: -0.085em;
        line-height: 0.95;
      }
      .home-lead {
        max-width: 620px;
        margin: 0 auto 31px;
        color: var(--muted);
        font-size: 1.12rem;
        line-height: 1.7;
      }
      .home-primary-button {
        display: inline-flex;
        align-items: center;
        min-height: 52px;
        padding: 0 25px;
        border-radius: 10px;
        background: var(--blue);
        color: var(--white);
        font-weight: 800;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
      }
      .home-primary-button:hover {
        background: var(--navy);
        transform: translateY(-2px);
      }
      .hosting-options {
        padding: 84px 0 105px;
      }
      .home-section-heading {
        max-width: 660px;
        margin: 0 auto 38px;
        text-align: center;
      }
      .home-section-heading h2 {
        margin: 8px 0 12px;
        color: var(--navy);
        font-size: clamp(2.1rem, 4vw, 3.2rem);
        letter-spacing: -0.07em;
        line-height: 1;
      }
      .home-section-heading > p:last-child {
        margin: 0;
        color: var(--muted);
      }
      .hosting-option-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
      }
      .hosting-option-card {
        display: flex;
        min-height: 310px;
        flex-direction: column;
        padding: 28px;
        border: 1px solid var(--line);
        border-radius: 18px;
        background: var(--white);
        box-shadow: 0 12px 30px rgba(20, 43, 85, 0.07);
        transition:
          transform 0.2s ease,
          box-shadow 0.2s ease,
          border-color 0.2s ease;
      }
      .hosting-option-card:hover {
        border-color: #b7cbe8;
        box-shadow: var(--shadow);
        transform: translateY(-5px);
      }
      .hosting-option-card-featured {
        border: 2px solid #7b91ee;
      }
      .hosting-option-icon {
        display: grid;
        width: 47px;
        height: 47px;
        place-items: center;
        margin-bottom: 22px;
        border-radius: 13px;
        background: #e5efff;
        color: var(--blue);
        font-size: 1.15rem;
        font-weight: 850;
      }
      .hosting-option-label {
        margin: -43px 0 28px 61px;
        color: var(--blue);
        font-size: 0.7rem;
        font-weight: 850;
        letter-spacing: 0.1em;
        text-transform: uppercase;
      }
      .hosting-option-card h3 {
        margin: 0 0 10px;
        color: var(--navy);
        font-size: 1.5rem;
        letter-spacing: -0.04em;
      }
      .hosting-option-card p:not(.hosting-option-label) {
        margin: 0;
        color: var(--muted);
        line-height: 1.65;
      }
      .hosting-option-link {
        display: flex;
        justify-content: space-between;
        margin-top: auto;
        padding-top: 24px;
        color: var(--blue);
        font-size: 0.9rem;
        font-weight: 800;
      }
      .hosting-option-link span {
        font-size: 1.15rem;
        transition: transform 0.2s ease;
      }
      .hosting-option-card:hover .hosting-option-link span {
        transform: translateX(4px);
      }
      .site-footer {
        display: flex;
        justify-content: space-between;
        padding: 25px 0;
        color: var(--muted);
        font-size: 0.8rem;
      }

      @media (max-width: 860px) {
        .plans {
          grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .checkout-layout {
          grid-template-columns: 1fr;
        }
      }
      @media (max-width: 640px) {
        .page-width {
          width: min(100% - 32px, 560px);
        }
        .site-header {
          min-height: 68px;
          padding-inline: 16px;
        }
        .main-nav {
          gap: 16px;
        }
        .page-banner {
          padding: 48px 0 42px;
        }
        .pricing,
        .checkout-page {
          padding: 52px 0 65px;
        }
        .billing-tabs {
          width: 100%;
          display: grid;
          grid-template-columns: repeat(2, 1fr);
        }
        .billing-tab {
          min-width: 0;
          padding-inline: 8px;
        }
        .billing-tab::before {
          display: none;
        }
        .billing-note {
          margin-bottom: 30px;
        }
        .plans,
        .hosting-option-grid {
          grid-template-columns: 1fr;
        }
        .plan-card,
        .plan-card-featured {
          min-height: 0;
        }
        .checkout-panel,
        .order-summary {
          padding: 24px 20px;
        }
        .domain-search-row {
          display: grid;
        }
        .domain-search-row select,
        .domain-search-row button {
          width: 100%;
        }
        .home-hero {
          padding: 78px 0 84px;
        }
        .home-hero h1 {
          font-size: clamp(3rem, 15vw, 4.3rem);
        }
        .hosting-options {
          padding: 64px 0 76px;
        }
        .site-footer {
          flex-direction: column;
          gap: 4px;
        }
      }

      /* VPS: circular plan markers and clean comparison panels. */
      .vps-theme .plans {
        gap: 22px;
        margin-top: 78px;
      }
      .vps-theme .plan-card,
      .vps-theme .plan-card-featured {
        min-height: 590px;
        padding: 116px 28px 22px;
        border: 0;
        border-radius: 4px;
        box-shadow: 0 15px 32px rgba(20, 43, 85, 0.1);
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
      .vps-theme .plan-circle-basic {
        background: #1768ed;
      }
      .vps-theme .plan-circle-standard {
        background: #0c638f;
      }
      .vps-theme .plan-circle-advanced {
        background: #303034;
      }
      .vps-theme .plan-circle-premium {
        background: #3f3f43;
      }
      .vps-theme .plan-circle-enterprise {
        background: #5a4778;
      }
      .vps-theme .plan-description {
        display: none;
      }
      .vps-theme .price {
        justify-content: center;
        margin-bottom: 25px;
        padding-bottom: 22px;
        border-bottom: 1px solid #d7dbe1;
        font-size: 2.7rem;
        font-weight: 350;
        letter-spacing: -0.045em;
      }
      .vps-theme .currency {
        color: var(--ink);
        font-size: 2.15rem;
        font-weight: 350;
      }
      .vps-theme .price small {
        margin-left: 7px;
        font-size: 0.82rem;
      }
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
        font-size: 0.88rem;
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
      .vps-theme .plan-card > ul:not(.extra-features) li strong {
        color: #17233a;
        font-weight: 800;
      }
      .vps-theme .features-toggle {
        display: flex;
      }
      .vps-theme .purchase-button {
        order: 5;
        min-height: 56px;
        margin: auto -28px -22px;
        border-radius: 0 0 4px 4px;
        background: #1768ed;
        box-shadow: none;
      }
      .vps-theme .plan-card:nth-child(2) .purchase-button {
        background: #0c638f;
      }
      .vps-theme .plan-card:nth-child(3) .purchase-button {
        background: #303034;
      }
      .vps-theme .plan-card:nth-child(4) .purchase-button {
        background: #3f3f43;
      }
      .vps-theme .plan-card:nth-child(5) .purchase-button {
        background: #5a4778;
      }
      .vps-theme .purchase-button:hover {
        filter: brightness(0.9);
      }
      .vps-theme .popular-badge {
        top: 0;
        right: 0;
        left: 0;
        border-radius: 0;
        background: linear-gradient(100deg, #315be8, #7041ca);
        color: #fff;
        text-align: center;
      }
      .vps-theme .cheapest-badge {
        top: 16px;
        left: 16px;
        right: auto;
      }

      /* Dedicated: compact business cards with a cyan conversion action. */
      .dedicated-theme .plans {
        gap: 20px;
        margin-top: 6px;
      }
      .dedicated-theme .plan-card,
      .dedicated-theme .plan-card-featured {
        min-height: 430px;
        padding: 28px 26px 0;
        border: 2px solid #e0e8f0;
        border-radius: 18px;
        box-shadow:
          0 6px 0 #e8eef3,
          0 14px 30px rgba(20, 43, 85, 0.07);
      }
      .dedicated-theme .plan-card:hover,
      .dedicated-theme .plan-card-featured:hover {
        border-color: #08a1d0;
        box-shadow:
          0 6px 0 #e1e8ed,
          0 18px 34px rgba(20, 43, 85, 0.13);
      }
      .dedicated-theme .plan-card h3.plan-circle,
      .dedicated-theme .plan-card-featured h3.plan-circle {
        width: auto;
        min-height: 0;
        margin-bottom: 22px;
        padding: 0;
        background: transparent;
        color: #718096;
        font-size: 0.9rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
      }
      .dedicated-theme .plan-description {
        display: none;
      }
      .dedicated-theme .price {
        margin-bottom: 22px;
        font-size: 2.7rem;
      }
      .dedicated-theme .currency {
        color: var(--ink);
      }
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
        font-size: 0.9rem;
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
        font-size: 0.75rem;
      }
      .dedicated-theme .plan-card > ul:not(.extra-features) li strong {
        color: #142033;
        font-weight: 800;
      }
      .dedicated-theme .features-toggle {
        margin-top: auto;
        border: 2px solid #0497ce;
        color: #0589b8;
      }
      .dedicated-theme .purchase-button {
        order: 5;
        min-height: 44px;
        margin: 16px -26px -2px;
        border: 0;
        border-radius: 9px 9px 14px 14px;
        background: #0497ce;
        box-shadow: none;
      }
      .dedicated-theme .purchase-button:hover {
        background: #047da9;
      }
      .dedicated-theme .popular-badge {
        display: none;
      }

      /* Shared: friendly, spacious cards with a stronger featured plan. */
      .shared-theme .plans {
        gap: 20px;
        margin-top: 8px;
      }
      .shared-theme .plan-card,
      .shared-theme .plan-card-featured {
        min-height: 575px;
        padding: 27px 23px 22px;
        border-top: 4px solid var(--blue);
        border-radius: 15px;
      }
      .shared-theme .plan-card-featured {
        border-color: #6969ef;
        background: linear-gradient(180deg, #fff 0%, #f8f8ff 100%);
      }
      .shared-theme .plan-card h3.plan-circle {
        margin-bottom: 17px;
      }
      .shared-theme .plan-description {
        min-height: 25px;
      }
      .shared-theme .plan-card > ul:not(.extra-features) {
        display: grid;
        gap: 0;
        margin: 5px 0 0;
        padding: 14px 0 0;
        border-top: 1px solid var(--line);
      }
      .shared-theme .plan-card > ul:not(.extra-features) li {
        min-height: 34px;
        padding: 7px 0;
        color: var(--muted);
        font-size: 0.87rem;
      }
      .shared-theme .plan-card > ul:not(.extra-features) li::before {
        margin-right: 8px;
        color: #0796a0;
        content: "";
        font-weight: 800;
      }
      .shared-theme .plan-card > ul:not(.extra-features) li strong {
        color: var(--ink);
        font-weight: 800;
      }
      .shared-theme .purchase-button {
        margin-top: 4px;
      }

      @media (max-width: 640px) {
        .vps-theme .plans {
          margin-top: 68px;
        }
        .vps-theme .plan-card,
        .vps-theme .plan-card-featured {
          min-height: 560px;
          padding-inline: 24px;
        }
        .vps-theme .purchase-button {
          margin-inline: -24px;
        }
      }
    </style>
    <style>
      .hosting-menu summary::after {
        content: "\25BE";
      }
      .feature-group li::before {
        content: "\2713";
      }
      .feature-group li.feature-unavailable::before {
        content: "\00D7";
      }
      .vps-theme .plan-card > ul:not(.extra-features) li::before,
      .shared-theme .plan-card > ul:not(.extra-features) li::before {
        content: "\2713";
      }
      .dedicated-theme .plan-card > ul:not(.extra-features) li::before {
        content: "\2713";
      }
      .domain-option-icon {
        font-family: Arial, sans-serif;
      }
    </style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/hosting-nav.css') }}?v={{ md5_file(public_path('css/hosting-nav.css')) }}">
  </head>
  <body>
<div class="hosting-nav">
<div class="site-top"><div class="site-wrap site-top-inner"><div class="site-top-links"><a class="coming-soon-trigger" href="#" data-service="Blog">Blog</a><a class="coming-soon-trigger" href="#" data-service="Offers">Offers</a><span>☎ {{ \App\Models\Setting::get('support_phone') }}</span></div><div class="site-top-links"><a href="{{ route('cart.index') }}">🛒 Cart <b class="site-count">{{ $cartCount }}</b></a></div></div></div>
<header class="site-header"><div class="site-wrap site-header-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a><nav class="site-nav"><a href="{{ route('landing') }}">Home</a><div class="site-nav-dropdown"><a href="{{ route('domains.index') }}">Domains</a><div class="site-submenu"><a href="{{ route('domains.index') }}">Domain Registration</a><a class="coming-soon-trigger" href="#" data-service="Domain Transfer">Domain Transfer</a></div></div><div class="site-nav-dropdown"><a class="selected" href="{{ route('hosting.shared') }}">Web Hosting</a><div class="site-submenu"><a href="{{ route('hosting.shared') }}">Shared Hosting</a><a href="{{ route('hosting.vps') }}">VPS Hosting</a><a href="{{ route('hosting.dedicated') }}">Dedicated Hosting</a></div></div><a class="coming-soon-trigger" href="#" data-service="Email hosting">Email</a><a class="coming-soon-trigger" href="#" data-service="SMS Service">SMS Service</a><a href="{{ route('support') }}">Support</a></nav><div class="site-account">@if(session('customer'))@include('partials.client-area-menu')<button class="site-user" type="button" onclick="event.stopPropagation(); document.getElementById('accountMenu').classList.toggle('open');">@if(session('customer.logo'))<img class="site-avatar" src="{{ session('customer.logo') }}" alt="">@else<span class="site-avatar">{{ session('customer.initials') ?: \App\User::initials(session('customer.name')) }}</span>@endif<span>{{ session('customer.name') }}</span> ⌄</button><div class="site-menu" id="accountMenu"><div class="site-menu-head"><strong>{{ session('customer.name') }}</strong><span>{{ session('customer.email') }}</span></div><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('mystore') }}">My Store</a><a href="{{ route('wallet.index') }}">Wallet · ৳{{ number_format(session('customer.balance', 0), 2) }}</a><a href="{{ route('invoices') }}">Invoices</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div>@else<a class="site-btn-ghost" href="{{ route('login') }}">Log in</a><a class="site-btn" href="{{ route('register') }}">Sign up</a>@endif</div></div></header>
@include('partials.coming-soon-modal')
</div>

    <nav class="hosting-rail" aria-label="Switch hosting type">
        <a href="{{ route('hosting.shared') }}" class="is-active" aria-current="page"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg><strong>Shared</strong></a>
        <a href="{{ route('hosting.vps') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="2"/><rect x="3" y="13.5" width="18" height="6.5" rx="2"/><path d="M7 7.2h.01M7 16.7h.01"/></svg><strong>VPS</strong></a>
        <a href="{{ route('hosting.dedicated') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2"/><rect x="10.5" y="10.5" width="3" height="3"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/></svg><strong>Dedicated</strong></a>
    </nav>
    <section class="page-banner page-banner-compact page-banner-luxe">
      <div class="page-width">
        <p class="banner-kicker">Shared hosting</p>
        <h1>Shared Hosting</h1>
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="{{ route('landing') }}">Home</a>
          <span>/</span>
          <strong>Shared Hosting</strong>
        </nav>
      </div>
    </section>

    <main class="pricing page-width shared-theme">
      <div class="hosting-bar hosting-bar-edge hosting-bar-half">
      <nav aria-label="Hosting type">
        <a href="{{ route('hosting.shared') }}" aria-current="page"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg><strong>Shared</strong></a>
        <a href="{{ route('hosting.vps') }}"><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="2"/><rect x="3" y="13.5" width="18" height="6.5" rx="2"/><path d="M7 7.2h.01M7 16.7h.01"/></svg><strong>VPS</strong></a>
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
@media (max-width:640px){.hosting-bar nav{width:100%;justify-content:space-between}.hosting-bar a{flex:1;text-align:center;padding:9px 8px}}
      </style>
        <style>
.hosting-bar-half{position:static;display:flex;justify-content:flex-start;margin:0 0 24px;padding:0;background:transparent;border:0;animation:none;pointer-events:none}
.hosting-bar-half nav{display:flex;align-items:flex-start;gap:0;padding:0;border:0;background:transparent;box-shadow:none;backdrop-filter:none;pointer-events:auto}
.hosting-bar-half a{position:relative;display:inline-flex;flex-direction:column;align-items:center;gap:5px;width:120px;margin-left:-16px;padding:10px 8px 15px;overflow:hidden;text-align:center;background:linear-gradient(180deg,#ffffff 0%,#e9eefa 100%);border:1px solid #9fb3d3;border-radius:0 0 60px 60px / 0 0 32px 32px;box-shadow:0 4px 12px rgba(16,34,71,.08);transition:transform .25s ease,filter .25s ease,box-shadow .25s ease}
.hosting-bar-half a:first-child{margin-left:0}
.hosting-bar-half .di-svg{width:18px;height:18px;color:#2f73e8;transition:color .25s,transform .25s}
.hosting-bar-half strong{font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#2c3a55}
.hosting-bar-half a:hover{transform:translateY(2px);filter:brightness(1.05);box-shadow:0 8px 18px rgba(47,115,232,.22)}
.hosting-bar-half a:hover .di-svg{transform:translateY(-1px)}
.hosting-bar-half a[aria-current="page"]{z-index:3;width:132px;margin-top:0;padding:12px 10px 17px;background:linear-gradient(180deg,#4f8df7 0%,#2f73e8 55%,#1d4fb0 100%);border-color:#1d4fb0;box-shadow:0 10px 24px rgba(47,115,232,.5);color:#fff}
.hosting-bar-half a[aria-current="page"] .di-svg{color:#fff}
.hosting-bar-half a[aria-current="page"] strong{color:#fff}
.hosting-bar-half a[aria-current="page"]::after{content:"";position:absolute;inset:0;background:linear-gradient(115deg,transparent 32%,rgba(255,255,255,.28) 48%,transparent 62%);transform:translateX(-130%);animation:halfSheen 4.5s ease infinite}
@keyframes halfSheen{0%,60%{transform:translateX(-130%)}90%,100%{transform:translateX(130%)}}
@media(max-width:640px){.hosting-bar-half{margin:0 0 18px}.hosting-bar-half nav{width:auto;padding:0}.hosting-bar-half a{flex:1;width:auto;min-width:0;margin-left:-10px;padding:8px 4px 12px;border-radius:0 0 44px 44px / 0 0 24px 24px}.hosting-bar-half a[aria-current="page"]{width:auto;margin:0}.hosting-bar-half .di-svg{width:15px;height:15px}.hosting-bar-half strong{font-size:.68rem}}
@media(prefers-reduced-motion:reduce){.hosting-bar-half a[aria-current="page"]::after{animation:none}}
        </style>
      <div class="billing-row">
      <div class="billing-tabs" role="tablist" aria-label="Billing period">
        <button
          class="billing-tab is-active"
          type="button"
          role="tab"
          aria-selected="true"
          data-period="monthly"
          data-soon="{{ $terms['monthly']['soon'] ? 'true' : 'false' }}"
        >
          Monthly
        </button>
        <button
          class="billing-tab"
          type="button"
          role="tab"
          aria-selected="false"
          data-period="yearly"
          data-soon="{{ $terms['yearly']['soon'] ? 'true' : 'false' }}"
        >
          Yearly
        </button>
        <button
          class="billing-tab"
          type="button"
          role="tab"
          aria-selected="false"
          data-period="twoYear"
          data-soon="{{ $terms['twoYear']['soon'] ? 'true' : 'false' }}"
        >
          2 Years
        </button>
        <button
          class="billing-tab"
          type="button"
          role="tab"
          aria-selected="false"
          data-period="threeYear"
          data-soon="{{ $terms['threeYear']['soon'] ? 'true' : 'false' }}"
        >
          3 Years
        </button>
      </div>
      </div>
      <p class="billing-note" id="billing-note">
        Flexible month-to-month billing
      </p>

      <style>
.shared-theme .plan-card li::before{display:inline-grid;place-items:center;vertical-align:middle;width:20px;height:20px;margin-right:9px;border-radius:6px;background:#2f73e8;color:#fff;font-family:"Segoe UI Symbol","Noto Sans Symbols 2",sans-serif;font-size:.78rem;font-weight:400;box-shadow:0 4px 10px rgba(47,115,232,.35)}
.shared-theme .plan-card li[data-icon="check"]::before{content:"\2713"}
.shared-theme .plan-card li[data-icon="storage"]::before{content:"\25A4"}
.shared-theme .plan-card li[data-icon="transfer"]::before{content:"\21C4"}
.shared-theme .plan-card li[data-icon="ftp"]::before{content:"\21C5"}
.shared-theme .plan-card li[data-icon="website"]::before{content:"\2302"}
.shared-theme .plan-card li[data-icon="mail"]::before{content:"\2709"}
.shared-theme .plan-card li[data-icon="db"]::before{content:"\25A6"}
.shared-theme .plan-card li[data-icon="subdomain"]::before{content:"\2316"}
.shared-theme .plan-card li[data-icon="ecommerce"]::before{content:"\25C8"}
.shared-theme .plan-card li[data-icon="remote"]::before{content:"\21D4"}
.shared-theme .plan-card li[data-icon="postgres"]::before{content:"\25A9"}
.shared-theme .plan-card li[data-icon="phppgadmin"]::before{content:"\25A7"}
.shared-theme .plan-card li[data-icon="ssh"]::before{content:"\2318"}
.shared-theme .plan-card li[data-icon="shell"]::before{content:"\25CD"}
.shared-theme .plan-card li[data-icon="speed"]::before{content:"\26A1"}
.shared-theme .plan-card li[data-icon="cpu"]::before{content:"\2699"}
.shared-theme .plan-card li.feature-unavailable::before{content:"\00D7";background:#fff1f1;color:#e53935;box-shadow:none}
      </style>
      <div class="coming-soon-panel" hidden>
        <span class="coming-soon-pill">Coming soon</span>
        <h3>Long-term billing is on its way.</h3>
        <p>2-year and 3-year plans aren't available yet. Choose monthly or yearly to get started today.</p>
        <button class="coming-soon-back" type="button">View monthly plans</button>
      </div>
      <div class="plans" aria-label="Shared hosting plans">
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
          <p class="dialog-eyebrow">Access control</p>
          <h2 id="dialog-title">More features</h2>
        </div>
        <button
          class="dialog-close"
          type="button"
          aria-label="Close feature list"
        >
          &#215;
        </button>
      </div>
      <div class="dialog-summary" id="dialog-summary"></div>
      <p class="dialog-acl-note">CMS control is included with this plan.</p>
    </dialog>

    <script>
      // Builds the grouped feature list of one card from two source lists
      // (primary rows + extra rows). Used for the initial render and again
      // whenever the billing tab changes — the CMS stores separate feature
      // rows per billing term (template.feature-set on each card).
      const buildSharedFeatureGroups = (card, primaryItems, extraItems) => {
        let featureGroups = card.querySelector(":scope > .feature-groups");
        if (!featureGroups) {
          featureGroups = document.createElement("div");
          featureGroups.className = "feature-groups";
          card
            .querySelector(":scope > ul:not(.extra-features)")
            .replaceWith(featureGroups);
          const extraList = card.querySelector(":scope > .extra-features");
          if (extraList) extraList.remove();
        }
        featureGroups.innerHTML = "";

        const groups = [
          { title: "Core resources", features: primaryItems },
          {
            title: "Account & databases",
            features: extraItems.filter(
              (feature) => !feature.classList.contains("feature-unavailable"),
            ),
          },
          {
            title: "Advanced & security",
            features: extraItems.filter((feature) =>
              feature.classList.contains("feature-unavailable"),
            ),
          },
        ];

        groups.forEach(({ title, features }) => {
          if (!features.length) {
            return;
          }
          const group = document.createElement("section");
          group.className = "feature-group";
          group.innerHTML = `<h4>${title}</h4>`;
          const list = document.createElement("ul");
          features.forEach((feature) => {
            if (!feature.querySelector("strong")) {
              const match = feature.textContent
                .trim()
                .match(/^(\d+(?:\s*(?:GB|TB))?)(.*)$/);
              if (match) {
                feature.innerHTML = `<strong>${match[1]}</strong>${match[2]}`;
              }
            }
            list.append(feature);
          });
          group.append(list);
          featureGroups.append(group);
        });

        featureGroups.querySelectorAll("li").forEach((item) => {
          const text = item.textContent.toLowerCase();
          let icon = "check";
          if (text.includes("subdomain")) icon = "subdomain";
          else if (text.includes("remote")) icon = "remote";
          else if (
            text.includes("e-commerce") ||
            text.includes("ecommerce")
          )
            icon = "ecommerce";
          else if (text.includes("postgresql")) icon = "postgres";
          else if (text.includes("phppgadmin")) icon = "phppgadmin";
          else if (text.includes("ssh") || text.includes("terminal"))
            icon = "ssh";
          else if (text.includes("shell") || text.includes("jailed"))
            icon = "shell";
          else if (text.includes("eaccelerator")) icon = "speed";
          else if (
            text.includes("nvme") ||
            text.includes("storage") ||
            text.includes("disk")
          )
            icon = "storage";
          else if (text.includes("bandwidth")) icon = "transfer";
          else if (text.includes("ftp")) icon = "ftp";
          else if (
            text.includes("website") ||
            text.includes("add-on") ||
            text.includes("addon")
          )
            icon = "website";
          else if (text.includes("mail")) icon = "mail";
          else if (text.includes("mysql") || text.includes("database"))
            icon = "db";
          else if (text.includes("cpu") || text.includes("core"))
            icon = "cpu";
          item.dataset.icon = icon;
        });
      };

      document.querySelectorAll(".plan-card").forEach((card) => {
        const primaryList = card.querySelector(
          ":scope > ul:not(.extra-features)",
        );
        const extraList = card.querySelector(":scope > .extra-features");
        if (primaryList && extraList) {
          buildSharedFeatureGroups(
            card,
            [...primaryList.children],
            [...extraList.children],
          );
        }
      });

      const periodNotes = {
        monthly: "Flexible month-to-month billing",
        yearly: "Annual hosting package pricing",
        twoYear: "Two-year plan pricing",
        threeYear: "Three-year plan pricing",
      };
      const periodUnits = {
        monthly: "/ month",
        yearly: "/ year",
        twoYear: "/ 2 years",
        threeYear: "/ 3 years",
      };
      // data-soon-monthly / -yearly / -twoyear / -threeyear on .price-value
      // let every card flag its own billing terms independently.
      const soonKey = (period) =>
        "soon" + period.charAt(0).toUpperCase() + period.slice(1).toLowerCase();

      document.querySelectorAll(".plan-card").forEach((card) => {
        const purchaseButton = card.querySelector(".purchase-button");
        purchaseButton.dataset.defaultLabel =
          purchaseButton.querySelector("span").textContent;
        purchaseButton.dataset.baseHref = purchaseButton.href;
      });

      const applyPeriod = (period) => {
        document.querySelectorAll(".plan-card").forEach((card) => {
          // Card name follows the billing term (per-term CMS names).
          const planTitle = card.querySelector("h3.plan-circle");
          const titleKey = period + "Name";
          if (planTitle && planTitle.dataset[titleKey]) {
            planTitle.textContent = planTitle.dataset[titleKey];
          }

          const price = card.querySelector(".price-value");
          const purchaseButton = card.querySelector(".purchase-button");
          const label = purchaseButton.querySelector("span");
          const unit = card.querySelector(".price-unit");
          const soon = price.dataset[soonKey(period)] === "true";
          const visible = card.dataset["visible" + period.charAt(0).toUpperCase() + period.slice(1).toLowerCase()] === "true";

          card.hidden = !visible;
          card.classList.toggle("is-coming-soon", soon);
          price.textContent = soon ? "Coming soon" : price.dataset[period];
          label.textContent = soon
            ? "Coming soon"
            : purchaseButton.dataset.defaultLabel;
          purchaseButton.setAttribute(
            "aria-disabled",
            soon ? "true" : "false",
          );
          if (soon) {
            purchaseButton.href = "#";
            unit.textContent = "";
          } else {
            const url = new URL(
              purchaseButton.dataset.baseHref,
              window.location.href,
            );
            url.searchParams.set("period", period);
            purchaseButton.href = url.toString();
            unit.textContent = periodUnits[period];
          }

          // Features: rebuild the groups from THIS term's template. The CMS
          // stores separate feature rows per billing term; a card whose
          // selected term is Coming soon keeps graying its current rows.
          const featureGroups = card.querySelector(".feature-groups");
          if (featureGroups) {
            if (soon) {
              featureGroups.querySelectorAll("li").forEach((feature) => {
                feature.className = "feature-unavailable";
                feature.innerHTML = "Coming soon";
              });
            } else {
              const template = card.querySelector(
                `template.feature-set[data-term="${period}"]`,
              );
              if (template) {
                const scope = template.content.cloneNode(true);
                buildSharedFeatureGroups(
                  card,
                  [...scope.querySelector("ul:not(.extra-features)").children],
                  [...scope.querySelector("ul.extra-features").children],
                );
              }
            }
          }
        });
      };

      const currentTab =
        document.querySelector(".billing-tab.is-active") ||
        document.querySelector(".billing-tab");
      if (currentTab) {
        applyPeriod(currentTab.dataset.period);
      }

      document.querySelectorAll(".billing-tab").forEach((tab) => {
        tab.addEventListener("click", () => {
          document.querySelectorAll(".billing-tab").forEach((item) => {
            const active = item === tab;
            item.classList.toggle("is-active", active);
            item.setAttribute("aria-selected", active ? "true" : "false");
          });
          applyPeriod(tab.dataset.period);
          // The tab is "Coming soon" only when NO visible card offers it.
          const tabSoon = tab.dataset.soon === "true";
          document.querySelector("#billing-note").textContent = tabSoon
            ? "Coming soon"
            : periodNotes[tab.dataset.period];
        });
      });

      document.querySelectorAll(".features-toggle").forEach((toggle) => {
        toggle.addEventListener("click", () => {
          const card = toggle.closest(".plan-card");
          const dialog = document.querySelector("#features-dialog");
          const title = card.querySelector("h3").textContent;
          const description =
            card.querySelector(".plan-description").textContent;
          const price = card.querySelector(".price").cloneNode(true);
          const summary = document.querySelector("#dialog-summary");
          document.querySelector("#dialog-title").textContent =
            `${title} plan features`;
          summary.innerHTML = `<p>${description}</p><div class="dialog-price"></div>`;
          summary.querySelector(".dialog-price").append(price);
          dialog.showModal();
        });
      });

      document.querySelectorAll(".purchase-button").forEach((button) => {
        button.addEventListener("click", (event) => {
          if (button.getAttribute("aria-disabled") === "true") {
            event.preventDefault();
          }
        });
      });

      const featuresDialog = document.querySelector("#features-dialog");
      featuresDialog
        .querySelector(".dialog-close")
        .addEventListener("click", () => featuresDialog.close());
      featuresDialog.addEventListener("click", (event) => {
        if (event.target === featuresDialog) {
          featuresDialog.close();
        }
      });
    </script>
    <script>
      (function () {
        var plans = document.querySelector(".plans");
        var panel = document.querySelector(".coming-soon-panel");
        if (!plans || !panel) return;
        function sync() {
          var active = document.querySelector(".billing-tab.is-active");
          var soon = active ? active.dataset.soon === "true" : false;
          plans.hidden = soon;
          panel.hidden = !soon;
        }
        document.querySelectorAll(".billing-tab").forEach(function (tab) {
          tab.addEventListener("click", sync);
        });
        var back = panel.querySelector(".coming-soon-back");
        if (back) {
          back.addEventListener("click", function () {
            var monthly = document.querySelector(
              '.billing-tab[data-period="monthly"]',
            );
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
