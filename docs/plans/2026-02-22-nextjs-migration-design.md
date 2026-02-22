# SGA Website: Next.js + Supabase Migration Design

## Goal

Replace the WordPress site with a Next.js + Supabase application that gives SGA staff a focused, mobile-first editing experience for the things they actually change (foster dogs, events, sponsors, page content, navigation) while giving visitors a fast, modern, persona-driven website optimized for both search engines and AI systems.

## Why Move Off WordPress

WordPress served as a starting point, but three problems emerged:

1. **Content-in-database breaks developer workflow.** Layouts, text, and configuration all live in the WordPress database, making git-based staging, review, and reproducible deploys painful. Every content change requires a database dump/restore cycle.

2. **WordPress complexity tax.** Plugins, PHP updates, security patches, and Gutenberg quirks are ongoing overhead for what is fundamentally a brochure site with structured data entry. Every UX improvement (RescueGroups integration, foster dog cards, mobile layout) required custom PHP development fighting WordPress conventions.

3. **Mobile editing is poor.** The WordPress block editor is not mobile-friendly. Staff (Lily, Jacintha) would prefer a simple, purpose-built editing experience over wrestling with Gutenberg on a phone.

## Architecture

```
Visitors       --> Vercel CDN (static + ISR pages)
                     |
                     |-- /foster/         <-- static, revalidates on dog changes
                     |-- /events/         <-- static, revalidates on event changes
                     |-- /adopt/          <-- SSR, calls RescueGroups API
                     |-- /about/, etc.    <-- fully static

Staff (logged in) --> Same site + edit controls appear
                     |
                     |-- Inline edit buttons on pages
                     |-- /admin/dogs      <-- manage foster dogs
                     |-- /admin/events    <-- manage events
                     |-- /admin/sponsors  <-- manage sponsors
                     |-- /admin/pages     <-- edit page content
                     |-- /admin/nav       <-- manage menu links
                     |-- /admin/team      <-- manage staff accounts (admin only)

Supabase --------> Postgres (foster dogs, events, sponsors, page content, nav links)
                |  Auth (staff logins via Google, email+password, or magic link)
                |  Storage (dog photos, sponsor logos, page images)
                |  Row Level Security (role-based write access)
```

**Stack:** Next.js 15 (App Router) + Supabase + Vercel

**What goes in git:** Page layouts, components, styles, navigation structure, brand tokens, Supabase migrations.

**What goes in Supabase:** Foster dogs, events, sponsors, editable text blocks within pages, images, auth.

### Deployment Workflow

| Environment | Branch | URL | When it updates |
|---|---|---|---|
| Local dev | any | localhost:3000 | Instantly on file save |
| Staging/preview | PR or `staging` branch | auto-generated Vercel preview URL | On push |
| Production | `main` | savinggreatanimals.org | On merge to main |

Developer controls when staging promotes to production (merge to main). Content edits by staff (foster dogs, events, page text) go live instantly via Supabase + ISR revalidation — no deploy needed.

### Going Live

When ready, point `savinggreatanimals.org` to Vercel by updating DNS records at GoDaddy (A record + CNAME). Vercel auto-provisions HTTPS. The WordPress site on Railway stays up as a fallback until the cutover is confirmed.

## Data Model

Five tables in Supabase, each mapping to a real editing need.

### foster_dogs

| Column | Type | Notes |
|---|---|---|
| id | uuid | Primary key |
| name | text | Dog name |
| breed | text | "Jindo", "Rat Terrier" |
| age | text | "2 years", "6-7 weeks" |
| urgency | enum | `urgent`, `needed`, `secured` |
| notes | text | Description, temperament, special needs |
| photo_path | text | Path in Supabase Storage |
| sort_order | int | Manual ordering within urgency tier |
| created_at | timestamp | Auto-set |
| updated_at | timestamp | Auto-set |

This schema will evolve. Adding a column is a one-line Supabase migration (SQL file in git). The edit forms are code we control, so adding a field is: write migration, add to form config, deploy.

### events

| Column | Type | Notes |
|---|---|---|
| id | uuid | Primary key |
| title | text | "Dog Wash Fundraiser" |
| date_start | timestamp | Event start |
| date_end | timestamp | Event end (nullable) |
| location | text | "Magnuson Park, Seattle" |
| description | text | Rich text (markdown) |
| external_url | text | Eventbrite/signup link (nullable) |
| photo_path | text | Optional event image |
| published | boolean | Draft vs. live |
| created_at | timestamp | Auto-set |

### sponsors

| Column | Type | Notes |
|---|---|---|
| id | uuid | Primary key |
| name | text | Sponsor name |
| logo_path | text | Path in Supabase Storage |
| url | text | Sponsor website (nullable) |
| sort_order | int | Display order |
| active | boolean | Show/hide without deleting |

### page_content

| Column | Type | Notes |
|---|---|---|
| id | uuid | Primary key |
| page_slug | text | "about", "foster", "donate" — matches the route |
| section_key | text | "hero_title", "hero_subtitle", "body", "cta_text" |
| content | text | Editable text (markdown for rich sections) |
| updated_at | timestamp | Auto-set |
| updated_by | uuid | FK to auth.users |

Each page has named sections that map to specific spots in the layout. The About page might have: `hero_title`, `hero_subtitle`, `main_body`, `cta_heading`, `cta_text`. Layouts are code; the text within them is editable.

### nav_links

| Column | Type | Notes |
|---|---|---|
| id | uuid | Primary key |
| label | text | "Merch", "Donate" |
| url | text | Internal path or external URL |
| position | text | "header", "footer", "mobile_bar" |
| sort_order | int | Display order within position |
| active | boolean | Show/hide |

### What stays out of the database

Page layouts, component structure, styles, brand colors, and the RescueGroups integration stay in code (git). The Available Dogs page calls the RescueGroups API directly (server-side in Next.js) — that data is managed in RescueGroups by the rescue.

## Authentication & Access Control

### Login Methods

Supabase Auth supports multiple providers simultaneously. Staff choose whichever they prefer:

- **Google login** — one-tap for those with Google accounts
- **Email + password** — Supabase handles hashing (bcrypt), password reset emails, email verification
- **Magic link** — passwordless email login (click link, you're in)

### Roles

A `profiles` table links Supabase auth IDs to roles:

| Role | Permissions |
|---|---|
| `admin` | All content, manage staff accounts, manage nav |
| `editor` | Manage dogs, events, sponsors, edit page content |

Lily and David are `admin`. Jacintha and other staff are `editor`.

Access is enforced at the database level via Supabase Row Level Security (RLS). Even if someone bypassed the UI, the database rejects unauthorized writes.

### Staff Account Management (`/admin/team`)

Admins can:

- **Invite** a new editor — enter email, pick role, Supabase sends an invite email with account setup link
- **Reset password** — triggers a Supabase password reset email to the user
- **Remove access** — deactivate account; past edits preserved for audit trail
- **Change role** — toggle between admin and editor

No custom auth code to build or maintain. The `/admin/team` page is a thin UI over Supabase Auth's admin API.

## Editing UX

Staff log in on the site itself — no separate admin URL, no wp-admin. Once authenticated, two editing surfaces appear:

### Inline Editing (on public pages)

When Jacintha visits `/foster/` while logged in:

- **"Add Dog"** button at the top of the foster dogs grid
- Each dog card gets a **pencil icon** — tap to open a slide-up edit panel (name, breed, age, urgency, notes, photo upload)
- **Reorder** dogs by drag-and-drop (or arrows on mobile)
- **Toggle** a dog to "Foster Secured" to move it down or hide it

Same pattern on other pages:

- `/events/` — "Add Event" button, edit icons on each event
- Sponsors section — "Manage Sponsors" opens a list editor
- Page text — edit icon on text blocks, inline editor appears

Edit panels are **mobile-first**: slide-up sheets on phone, side panels on desktop. Jacintha can add a foster dog from her phone at an intake.

### Admin Dashboard (`/admin`)

For bulk management:

| Route | Purpose |
|---|---|
| `/admin/dogs` | All foster dogs — filter by urgency, bulk reorder, archive |
| `/admin/events` | Upcoming and past events — create, edit, unpublish |
| `/admin/sponsors` | Logo grid with drag-to-reorder, add/remove |
| `/admin/pages` | List of pages with "Edit content" links, last-edited timestamps |
| `/admin/nav` | Manage header/footer/mobile-bar links |
| `/admin/team` | Invite, remove, reset password for staff (admin only) |

### What Editors Cannot Do

- Change page layouts or add new pages (code change, through developer)
- Install plugins or themes (there are none)
- Break styling or structure
- See anything technical — no database, no deploy logs

The editing surface is exactly as large as it needs to be.

## Visual Design

### Principles

- **Mobile-first** — designed for phones, scales up to desktop
- **Emotion-first** — real SGA dog photos, not stock. Every page should make you feel something
- **One clear action per page** — each persona has a primary CTA
- **Accessible** — WCAG 2.1 AA minimum

### Brand (carried over)

- **Colors:** Blue `#2B3990`, Orange `#E8772B`, Warm white `#FBF8F4`
- **Fonts:** DM Sans (body), Fraunces (headings)
- **Logo:** Existing SGA logo
- **Photography:** Real SGA dog photos from website, social media, and staff. No stock photos

### Persona-Driven Page Design

| Page | Primary Persona | Goal | Primary CTA |
|---|---|---|---|
| Homepage | All | Communicate mission, route to goal | Adopt / Foster / Donate |
| Available Dogs | Potential Adopter | Browse dogs, filter, see details | View Dog --> Apply to Adopt |
| Foster | Potential Foster | Understand fostering, see urgent dogs | Apply to Foster |
| About | Donor / general | Build trust, show impact | Donate |
| Donate | Donor | Frictionless giving | Donate Now |
| Get Involved / Ways to Help | Volunteer | Show concrete ways to help | Volunteer / Donate / Wish List |
| Events | Volunteer / Community | Find upcoming events | RSVP / Sign Up |
| Resources | Dog owners | Training, vets, lost pets, end of life | Informational |

### Mobile Layout

- **Navigation:** Hamburger menu + persistent bottom bar with key actions (Adopt, Donate)
- **Cards:** Foster dogs, available dogs, events render as full-width stacked cards on mobile
- **Forms:** All editing (staff) and action forms (visitor) designed as bottom sheets — slide up, feel native
- **Touch targets:** Minimum 44px, generous spacing

## SEO

### Technical (built into Next.js)

- **Metadata API** — per-route title, description, Open Graph, Twitter cards
- **Auto-generated sitemap** from routes + dynamic content
- **Structured data (JSON-LD)** on every page: Organization, LocalBusiness, Event, AnimalShelter schemas
- **Canonical URLs** — handled by Next.js
- **Performance** — static pages on CDN = fast Core Web Vitals

### Local/Geo SEO

- **Google Business Profile** — link to new site at cutover
- **NAP consistency** — Name, Address, Phone consistent across footer, About, structured data, Google Business
- **Location keywords** — "Seattle", "Greater Seattle area", "Washington state" in content naturally
- **Local schema** — `areaServed: "Seattle metropolitan area"` in Organization markup
- **Image alt text** — descriptive, location-inclusive where natural

## GEO (Generative Engine Optimization)

AI models (ChatGPT, Google AI Overview, Perplexity) can't read Facebook or WordPress databases. They read websites. Making the website the authoritative source for SGA information means AI systems surface SGA when someone asks "dog rescue near me in Seattle."

### Content Patterns for AI Extraction

- **Lead with concrete facts:** "Saving Great Animals has homed 8,500+ dogs since 2007" — not vague marketing copy
- **FAQ format:** Question/answer sections on foster, adoption, volunteer pages. AI models extract these directly for answers
- **Step-by-step processes:** "1. Submit application --> 2. Home visit --> 3. Two-week trial --> 4. Finalize adoption"
- **Entity clarity:** Always use "Saving Great Animals" (full name) in visible content, not just "SGA". Location explicit. Category explicit: "501(c)(3) nonprofit dog rescue organization"

### Content Depth

- Deep pages on fostering (guide, FAQ, active foster resources), adoption (process, fees, FAQ), volunteering
- Interlinked: foster page links to foster guide, dog profiles link to apply, events link to volunteer
- Regular updates (foster dogs, events) signal active maintenance

### Freshness

- Updated foster dogs and events show the site is current
- "Last updated" timestamps on resource pages

## Social Media Integration

Facebook is SGA's most active public channel. The website should complement it, not compete with it.

### Phase 1: Display & Link

- Social proof on homepage — "Follow us" links with Facebook and Instagram
- Share buttons on foster dog and event pages — pre-formatted social posts ("Meet Aiden, a Jindo looking for a foster home in Seattle!")
- Open Graph metadata — sharing a foster dog page on Facebook renders the dog's photo, name, and description automatically

### Phase 2: Embed & Surface

- "Latest from SGA" section on homepage using Meta's Page Plugin or Instagram Basic Display API
- Embeddable posts — staff paste a Facebook/Instagram URL into page content, it renders inline
- Photo gallery pulling from staff-uploaded images

### Phase 3: Website as Source of Truth

- Auto-posting — adding a foster dog or event on the site optionally auto-posts to Facebook/Instagram via Meta's API
- Success stories — marking a dog as "adopted" generates a shareable celebration post
- Event promotion — creating an event generates a social-optimized shareable link

### Why This Matters for GEO

AI models cannot read Facebook. Content that only exists on Facebook is invisible to ChatGPT, Perplexity, and Google AI Overview. Making the website the primary home for foster dogs, events, and stories — with Facebook as a distribution channel — makes SGA's information accessible to AI systems.

## Future Community Features

The auth system and database are already in place. Community features are incremental additions:

| Feature | Persona | Replaces |
|---|---|---|
| Foster Portal | Active fosters | Vet care spreadsheets, email-based supply/training requests, PDF guides |
| Adopter Dashboard | Adopters | Manual email back-and-forth on application status |
| Volunteer Hub | Volunteers | Event signups, orientation tracking, volunteer guide PDFs |
| Foster-to-Adopt Pipeline | Fosters + Adopters | Manual coordination by Jacintha |

Staff contacts currently handling these workflows via email:
- Melissa (melissa@savinggreatanimals.org) — training and transitions
- Carly (carly@savinggreatanimals.org) — vet appointments
- Lily (lily@savinggreatanimals.org) — supplies and volunteer support
- Jacintha (jacintha@savinggreatanimals.org) — adoption and placement

These features build on the same Supabase tables and auth. A foster logging a vet visit is the same pattern as an editor adding an event.

## Content Migration

The current savinggreatanimals.org has rich content beyond what we built in WordPress:

| Current Page | Content | Priority |
|---|---|---|
| Homepage | Hero, mission, CTAs | High |
| About + Vision/Mission | Org history, 8,500+ stat, matchmaking philosophy | High |
| Foster program | Crisis context, requirements, cost details, support | High |
| Foster FAQ | 6 detailed Q&As | High |
| Active foster resources | Vet spreadsheet, supply form, training form, contacts | Medium (becomes Portal) |
| Adoption info + fees | Process, trial period, home visits, fees | High |
| Adoption FAQ | Common questions | High |
| Ways to Help | 7 paths: donate, Bark Buddies, events, Grounds & Hounds, Chewy wishlist, car donation, book | High |
| Volunteer info | Orientation, roles, guide PDF, waiver | Medium |
| Sponsorship | Packet, Bark Buddies monthly giving, contact | Medium |
| Events / Bark Benefit | Annual fundraiser at Hyatt Bellevue, Sep 25 | High |
| Resources: training | Training partner info | Medium |
| Resources: vet partners | Approved vet list | Medium |
| Resources: lost pets | Guidance for lost pet owners | Medium |
| Resources: end of life | End-of-life support resources | Medium |
| Surrender | Help for owners who can't keep their dog | Medium |
| Doug's Place | Historical/sentimental content | Low |
| Blog | Unknown volume | Low |
| Merch (Fourthwall) | External link to sga-shop.fourthwall.com | High (nav link) |

Photos and imagery should be sourced from the current site, SGA's Facebook/Instagram, and staff's photo collections.

## Migration Phases

### Phase 1: Foundation

- Next.js app with Supabase, deployed to Vercel
- Auth (Google + email/password + magic link) and role system
- Static pages with content from `page_content` table
- Admin dashboard skeleton
- Brand: colors, fonts, logo, mobile-first layout

### Phase 2: Foster Dogs + Events

- Foster dogs CRUD — migrate 10 dogs from WordPress
- Foster dog detail pages with "Apply to Foster" CTA
- Events CRUD — replaces The Events Calendar plugin
- Inline editing on these pages

### Phase 3: Full Feature Parity

- RescueGroups integration (Available Dogs — port existing API logic)
- Sponsors management (individually editable, replaces Canva collage)
- Nav link management
- Page content inline editing
- Ways to Help page with all 7 paths
- Resources section (training, vets, lost pets, end of life)

### Phase 4: Cutover

- Review with Lily and Jacintha — get them comfortable with editing
- Migrate remaining content from current site
- Point savinggreatanimals.org DNS to Vercel
- Shut down WordPress on Railway
- Update Google Business Profile link

Each phase is independently deployable. WordPress stays live as production until Phase 4.

## Hosting & Cost

| Service | Tier | Cost | What it provides |
|---|---|---|---|
| Vercel | Free (Hobby) | $0/mo | Hosting, CDN, preview deploys, HTTPS |
| Supabase | Free | $0/mo | 500MB Postgres, 1GB storage, 50K auth users, Auth |
| Domain (GoDaddy) | Existing | Already paid | savinggreatanimals.org DNS |
| **Total** | | **$0/mo** | vs. $5/mo Railway WordPress |

Upgrade path: Supabase Pro ($25/mo) adds automatic daily DB backups and 8GB database when needed. Vercel Pro ($20/mo) if traffic grows significantly. Neither is needed at launch.

DB backups on free tier: scheduled `pg_dump` via GitHub Action (nightly), stored in a private repo or Supabase Storage.

## What We Keep vs. Lose vs. Gain

| Keep | Lose (intentionally) | Gain |
|---|---|---|
| All page content | WordPress admin | Git-controlled layouts |
| Foster dog data and photos | Plugin ecosystem | Mobile-first editing for staff |
| RescueGroups integration | Block editor | One codebase (site + future community) |
| Brand (colors, fonts, logo) | Database-as-content model | Instant deploys with preview URLs |
| Domain and SEO URLs | PHP runtime | Zero server maintenance |
| Mobile bottom bar UX | Server patching | Custom editing UX for SGA's needs |
| | | SEO + GEO built in from day one |
| | | Social media integration path |
| | | Community features roadmap |
