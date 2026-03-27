<div align="center">

# WPTrustRocket

**Trusted Shops Bewertungen abrufen, kuratieren und anzeigen — direkt in WordPress.**

[![WordPress 5.8+](https://img.shields.io/badge/WordPress-5.8%2B-21759b?logo=wordpress&logoColor=white)](#voraussetzungen)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php&logoColor=white)](#voraussetzungen)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue)](#lizenz)
[![Version](https://img.shields.io/badge/Version-2.1.0-brightgreen)](#)

</div>

---

WPTrustRocket synchronisiert Kundenbewertungen automatisch ueber die Trusted Shops eTrusted API, speichert sie lokal in der WordPress-Datenbank und bietet flexible Darstellungsmoeglichkeiten per Shortcode, Oxygen Builder oder REST API.

## Features

- **API-Sync** — Automatischer OAuth2-Abruf aller Bewertungen ueber die eTrusted API mit Paginierung
- **Cron-Sync** — Konfigurierbare Intervalle: stuendlich, zweimal taeglich, taeglich oder woechentlich
- **Review-Gruppen** — Bewertungen in Gruppen organisieren, kuratieren und gezielt ausspielen
- **4 Layouts** — Grid, Slider, Liste oder Badge — per Shortcode oder Oxygen-Element steuerbar
- **Responsive** — Alle Layouts passen sich automatisch an Mobile, Tablet und Desktop an
- **Touch & Drag** — Slider folgt dem Finger in Echtzeit mit Richtungserkennung und Edge-Resistance
- **Styling** — Farben, Abstände, Schatten, Sternfarbe u.v.m. ueber CSS Custom Properties
- **JSON-LD Schema** — Automatisches AggregateRating-Markup fuer bessere Sichtbarkeit in Suchmaschinen
- **Oxygen Builder** — Native Elemente (`TrustRocket Reviews` + `TrustRocket Badge`) im visuellen Editor
- **REST API** — Eigene Endpoints fuer headless oder AJAX-basierte Nutzung
- **Saubere Deinstallation** — Entfernt alle Tabellen, Optionen und Cron-Jobs bei Plugin-Loeschung

---

## Voraussetzungen

| Anforderung | Minimum |
|---|---|
| WordPress | 5.8 |
| PHP | 7.4 |
| Trusted Shops | Client ID, Client Secret & TSID |

---

## Installation

```bash
# 1. Repository klonen
git clone https://github.com/MGue95/wptrustrocket.git

# 2. In das Plugin-Verzeichnis verschieben
cp -r wptrustrocket /path/to/wp-content/plugins/
```

Dann im WordPress-Admin unter **Plugins** aktivieren und unter **TrustRocket > Einstellungen** die API-Credentials eintragen.

---

## Konfiguration

| Option | Beschreibung |
|---|---|
| **Client ID** | OAuth2 Client ID von Trusted Shops |
| **Client Secret** | OAuth2 Client Secret |
| **TSID** | Trusted Shops ID (Channel Reference) |
| **Sync-Intervall** | `hourly` · `twicedaily` · `daily` · `weekly` |

Nach dem Speichern kann ueber das Dashboard ein manueller Sync ausgeloest oder die Verbindung getestet werden.

---

## Verwendung

### Shortcode: Bewertungen

```html
[wptrustrocket group="meine-gruppe" layout="slider" columns="3" count="6" autoplay="5000"]
```

<details>
<summary><strong>Alle Parameter</strong></summary>

| Parameter | Standard | Beschreibung |
|---|---|---|
| `group` | *(Pflicht)* | Slug der Bewertungsgruppe |
| `layout` | `grid` | `grid` · `slider` · `list` · `badge` |
| `columns` | `3` | Spaltenanzahl im Grid (1–6) |
| `count` | `0` | Max. Anzahl (0 = alle) |
| `min_rating` | `0` | Mindest-Sternebewertung |
| `orderby` | `sort_order` | `sort_order` · `date` · `rating` |
| `order` | `asc` | `asc` · `desc` |
| `autoplay` | `0` | Autoplay in ms, nur Slider (0 = aus) |
| `show_title` | `true` | Bewertungstitel anzeigen |
| `show_date` | `true` | Datum anzeigen |
| `show_author` | `true` | Autorname anzeigen |
| `show_rating` | `true` | Sterne anzeigen |

</details>

### Shortcode: Badge

```html
[wptrustrocket_badge group="meine-gruppe"]
```

Kompakte Bewertungsuebersicht mit Durchschnitt, Sternen und Gesamtanzahl.

### Oxygen Builder

Im Oxygen Editor unter dem **WPTrustRocket**-Bereich im Add-Panel:

| Element | Beschreibung |
|---|---|
| **TrustRocket Reviews** | Grid, Slider oder Liste mit allen Optionen im visuellen Editor |
| **TrustRocket Badge** | Bewertungs-Badge mit Gruppenauswahl |

---

## Styling

Alle visuellen Eigenschaften lassen sich per Shortcode-Attribut oder CSS Custom Property steuern:

```html
[wptrustrocket group="main" card_bg="#fff" star_color="#F5A623" card_radius="16px" gap="24px"]
```

<details>
<summary><strong>CSS Custom Properties</strong></summary>

| Attribut | CSS-Variable | Beispiel |
|---|---|---|
| `card_bg` | `--wptr-card-bg` | `#ffffff` |
| `card_border` | `--wptr-card-border` | `#e2e8f0` |
| `card_radius` | `--wptr-card-radius` | `12px` |
| `card_padding` | `--wptr-card-padding` | `24px` |
| `card_shadow` | `--wptr-card-shadow` | `0 1px 3px rgba(0,0,0,.05)` |
| `star_color` | `--wptr-star-color` | `#F5A623` |
| `star_size` | `--wptr-star-size` | `16px` |
| `title_color` | `--wptr-title-color` | `#1e293b` |
| `title_size` | `--wptr-title-size` | `15px` |
| `text_color` | `--wptr-text-color` | `#64748b` |
| `text_size` | `--wptr-text-size` | `14px` |
| `author_color` | `--wptr-author-color` | `#1e293b` |
| `date_color` | `--wptr-date-color` | `#64748b` |
| `gap` | `--wptr-gap` | `20px` |

</details>

### Responsive Breakpoints

| Breite | Grid | Slider | Liste |
|---|---|---|---|
| >= 900px | konfigurierte Spalten | 3 Slides | Zweispaltig |
| 600–899px | 2 Spalten | 2 Slides | Zweispaltig |
| < 600px | 1 Spalte | 1 Slide | Einspaltig gestapelt |

---

## Projektstruktur

```
wptrustrocket/
├── wptrustrocket.php              # Plugin-Bootstrap & Hook-Registration
├── uninstall.php                  # Saubere Deinstallation
├── assets/
│   ├── css/
│   │   ├── admin.css              # Admin-Dashboard Styles
│   │   └── frontend.css           # Responsive Frontend-Layouts
│   └── js/
│       ├── admin.js               # Admin-UI (Sync, Gruppen, Settings)
│       └── frontend.js            # Slider mit Touch-Drag & Mouse-Drag
├── includes/
│   ├── class-wptr-activator.php   # DB-Setup & Defaults bei Aktivierung
│   ├── class-wptr-admin.php       # Admin-Seiten (Dashboard, Reviews, Gruppen, Settings)
│   ├── class-wptr-api.php         # Trusted Shops OAuth2 & Review-Sync
│   ├── class-wptr-cron.php        # WP-Cron Scheduling
│   ├── class-wptr-db.php          # Datenbank-Abstraktionsschicht
│   ├── class-wptr-oxygen.php      # Oxygen Builder Elemente
│   ├── class-wptr-renderer.php    # HTML-Rendering (Grid / Slider / List / Badge)
│   ├── class-wptr-rest.php        # REST API v1 Endpoints
│   ├── class-wptr-schema.php      # JSON-LD AggregateRating
│   └── class-wptr-shortcode.php   # [wptrustrocket] & [wptrustrocket_badge]
└── languages/                     # i18n Uebersetzungsdateien
```

### Datenbank-Tabellen

| Tabelle | Zweck |
|---|---|
| `wp_wptr_reviews` | Bewertungen (Rating, Titel, Kommentar, Autor, Timestamps) |
| `wp_wptr_groups` | Bewertungsgruppen / Kategorien |
| `wp_wptr_group_reviews` | Zuordnung Reviews <-> Gruppen (m:n) mit Sortierung |

---

## Lizenz

GPL-2.0-or-later — siehe [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html).
