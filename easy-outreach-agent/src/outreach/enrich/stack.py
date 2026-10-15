"""Detección de tecnología del sitio (CMS, CRM, agenda, chat, analítica) por firmas en el HTML."""

from __future__ import annotations

import re
from typing import Any

from bs4 import BeautifulSoup

# categoría -> {tecnología: firmas (minúsculas) que la delatan en HTML o cabeceras}
SIGNATURES: dict[str, dict[str, tuple[str, ...]]] = {
    "cms": {
        "wordpress": ("/wp-content/", "/wp-includes/", "wp-json"),
        "wix": ("static.wixstatic.com", "wix.com website builder", "x-wix-request-id"),
        "shopify": ("cdn.shopify.com", "shopify.theme", "x-shopify-stage"),
        "squarespace": ("static1.squarespace.com", "squarespace-cdn"),
        "webflow": ("assets.website-files.com", "data-wf-site", "webflow.js"),
        "godaddy": ("img1.wsimg.com", "godaddy website builder"),
        "tiendanube": ("d26lpennugtm8s.cloudfront.net", "tiendanube"),
        "joomla": ("/media/jui/", "joomla!"),
        "drupal": ("drupal-settings-json", "/sites/default/files/"),
        "hubspot_cms": ("hs-sites.com", "hubspot-cms"),
    },
    "crm": {
        "hubspot": ("js.hs-scripts.com", "js.hsforms.net", "hs-analytics"),
        "zoho": ("salesiq.zoho", "zohopublic", "crm.zoho"),
        "salesforce": ("pardot.com", "force.com/servlet/servlet.webtolead"),
        "kommo": ("kommo.com", "amocrm"),
        "pipedrive": ("pipedrivewebforms", "leadbooster"),
        "activecampaign": ("activehosted.com", "trackcmp.net"),
    },
    "booking": {
        "calendly": ("calendly.com",),
        "doctoralia": ("doctoralia.com",),
        "agendapro": ("agendapro.com",),
        "booksy": ("booksy.com",),
        "acuity": ("acuityscheduling.com",),
        "google_calendar": ("calendar.app.google", "calendar.google.com/calendar/appointments"),
        "simplybook": ("simplybook.me",),
    },
    "chat": {
        "whatsapp_widget": ("wa.me/", "api.whatsapp.com/send", "joinchat", "whatsapp-button"),
        "tawk": ("embed.tawk.to",),
        "tidio": ("code.tidio.co",),
        "messenger": ("connect.facebook.net/es_la/sdk/xfbml.customerchat", "fb-customerchat"),
        "intercom": ("widget.intercom.io",),
        "crisp": ("client.crisp.chat",),
    },
    "analytics": {
        "ga4": ("gtag/js?id=g-",),
        "gtm": ("googletagmanager.com/gtm.js",),
        "meta_pixel": ("connect.facebook.net/en_us/fbevents.js", "fbq('init'"),
        "hotjar": ("static.hotjar.com",),
        "clarity": ("clarity.ms/tag",),
    },
}

_GENERATOR_CMS = {"wordpress": "wordpress", "wix": "wix", "joomla": "joomla", "drupal": "drupal"}


def detect_stack(html: str, headers: dict[str, str] | None = None) -> dict[str, Any]:
    headers = {k.lower(): v for k, v in (headers or {}).items()}
    haystack = (html + " " + " ".join(f"{k}: {v}" for k, v in headers.items())).lower()

    result: dict[str, Any] = {}
    for category, techs in SIGNATURES.items():
        hits = [name for name, marks in techs.items() if any(m in haystack for m in marks)]
        if hits:
            result[category] = hits

    soup = BeautifulSoup(html, "html.parser")
    generator = soup.find("meta", attrs={"name": re.compile("^generator$", re.I)})
    if generator and generator.get("content"):
        content = generator["content"]
        result["generator"] = content[:120]
        for key, cms in _GENERATOR_CMS.items():
            if key in content.lower() and cms not in result.get("cms", []):
                result.setdefault("cms", []).append(cms)

    if "cms" not in result:
        result["cms"] = ["a_la_medida"]
    return result


def detect_language(html: str) -> str | None:
    soup = BeautifulSoup(html, "html.parser")
    root = soup.find("html")
    lang = root.get("lang") if root else None
    return lang.strip()[:10] if lang and lang.strip() else None
