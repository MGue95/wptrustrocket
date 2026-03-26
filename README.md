# 🚀 WPTrustRocket

**Bewertungen von Trusted Shops abrufen, kuratieren und anzeigen — direkt in WordPress.**

WPTrustRocket synchronisiert Kundenbewertungen automatisch über die Trusted Shops API, speichert sie lokal in der Datenbank und bietet flexible Darstellungsmöglichkeiten per Shortcode, Oxygen Builder oder REST API.

---

## Features

| Feature | Beschreibung |
|---|---|
| **API-Sync** | Automatischer OAuth2-Abruf aller Bewertungen über die eTrusted API |
| **Cron-Sync** | Konfigurierbare Intervalle — stündlich, zweimal täglich, täglich oder wöchentlich |
| **Review-Gruppen** | Bewertungen in Gruppen organisieren, kuratieren und gezielt ausspielen |
| **Layouts** | Grid, Slider, Liste oder Badge — per Shortcode steuerbar |
| **Styling** | Farben, Abstände, Schatten, Sternfarbe u.v.m. über CSS-Variablen und Shortcode-Attribute |
| **JSON-LD Schema** | Automatisches AggregateRating-Markup für bessere Sichtbarkeit in Suchmaschinen |
| **Oxygen Builder** | Native Oxygen-Elemente für visuelle Integration im Page Builder |
| **REST API** | Eigene Endpoints für headless oder AJAX-basierte Nutzung |
| **Saubere Deinstallation** | Entfernt alle Tabellen, Optionen und Cron-Jobs bei Plugin-Löschung |

---

## Voraussetzungen

- WordPress **5.8+**
- PHP **7.4+**
- Trusted Shops API-Zugangsdaten (Client ID & Client Secret)

---

## Installation

1. Repository klonen oder als ZIP herunterladen:
   ```bash
   git clone https://github.com/MGue95/wptrustrocket.git
   ```
2. Den Ordner `wptrustrocket` in `/wp-content/plugins/` ablegen
3. Plugin im WordPress-Admin aktivieren
4. Unter **Einstellungen → WPTrustRocket** die API-Credentials eintragen

---

## Konfiguration

| Option | Beschreibung |
|---|---|
| **Client ID** | OAuth2 Client ID von Trusted Shops |
| **Client Secret** | OAuth2 Client Secret |
| **TSID** | Trusted Shops ID (Channel Reference) |
| **Sync-Intervall** | `hourly`, `twicedaily`, `daily` oder `weekly` |

Nach dem Speichern kann über den Admin-Bereich ein manueller Sync ausgelöst oder die Verbindung getestet werden.

---

## Shortcodes

### Bewertungen anzeigen

```
[wptrustrocket group="meine-gruppe" layout="grid" columns="3" count="6"]
```

**Parameter:**

| Parameter | Standard | Beschreibung |
|---|---|---|
| `group` | *(pflicht)* | Slug der Bewertungsgruppe |
| `layout` | `grid` | `grid`, `slider`, `list` oder `badge` |
| `columns` | `3` | Spaltenanzahl (Grid, 1–6) |
| `count` | `0` (alle) | Max. Anzahl Bewertungen |
| `min_rating` | `0` | Mindest-Sternebewertung |
| `orderby` | `sort_order` | Sortierung: `sort_order`, `date`, `rating` |
| `order` | `asc` | `asc` oder `desc` |
| `autoplay` | `0` | Autoplay-Intervall in ms (nur Slider) |
| `show_title` | `true` | Titel anzeigen |
| `show_date` | `true` | Datum anzeigen |
| `show_author` | `true` | Autorname anzeigen |
| `show_rating` | `true` | Sterne anzeigen |

### Badge anzeigen

```
[wptrustrocket_badge group="meine-gruppe"]
```

Zeigt eine kompakte Bewertungsübersicht mit Durchschnitt, Sternen und Anzahl.

### Styling per Shortcode

Alle visuellen Eigenschaften lassen sich direkt als Attribute übergeben:

```
[wptrustrocket group="main" card_bg="#ffffff" star_color="#F5A623" card_radius="12px" gap="20px"]
```

| Attribut | CSS-Variable |
|---|---|
| `card_bg` | `--wptr-card-bg` |
| `card_border` | `--wptr-card-border` |
| `card_radius` | `--wptr-card-radius` |
| `card_padding` | `--wptr-card-padding` |
| `card_shadow` | `--wptr-card-shadow` |
| `star_color` | `--wptr-star-color` |
| `star_size` | `--wptr-star-size` |
| `title_color` | `--wptr-title-color` |
| `title_size` | `--wptr-title-size` |
| `text_color` | `--wptr-text-color` |
| `text_size` | `--wptr-text-size` |
| `author_color` | `--wptr-author-color` |
| `date_color` | `--wptr-date-color` |
| `gap` | `--wptr-gap` |

---

## Projektstruktur

```
wptrustrocket/
├── wptrustrocket.php          # Plugin-Bootstrap
├── uninstall.php              # Saubere Deinstallation
├── assets/
│   ├── css/                   # Frontend-Styles
│   └── js/                    # Slider-Logik
└── includes/
    ├── class-wptr-activator.php   # DB-Setup bei Aktivierung
    ├── class-wptr-admin.php       # Admin-UI & Einstellungen
    ├── class-wptr-api.php         # Trusted Shops OAuth & Sync
    ├── class-wptr-cron.php        # WP-Cron Scheduling
    ├── class-wptr-db.php          # Datenbank-Layer (CRUD)
    ├── class-wptr-oxygen.php      # Oxygen Builder Integration
    ├── class-wptr-renderer.php    # HTML-Rendering (Grid/Slider/List/Badge)
    ├── class-wptr-rest.php        # REST API Endpoints
    ├── class-wptr-schema.php      # JSON-LD AggregateRating
    └── class-wptr-shortcode.php   # Shortcode-Handler
```

---

## Lizenz

GPL-2.0-or-later — siehe [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
