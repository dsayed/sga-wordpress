# SGA Next.js + Supabase Migration — Implementation Plan (Phases 1-2)

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build the SGA website as a Next.js + Supabase application with real content from savinggreatanimals.org, staff auth, and a focused editing UX for foster dogs and events.

**Architecture:** Next.js 15 App Router with Supabase for auth, database, and file storage. Static pages with ISR for dynamic content. Deployed to Vercel. Staff log in on the site itself and see edit controls — no separate admin panel. Content seeded from the live savinggreatanimals.org website.

**Tech Stack:** Next.js 15, React 19, TypeScript, Supabase (Postgres + Auth + Storage), Tailwind CSS 4, Vercel

**Local tools available:** Node 25.6, npm 11.9, Vercel CLI 50.18, no Supabase CLI (use `npx supabase`)

**New repo:** github.com/dsayed/sga-website

**Design doc:** `docs/plans/2026-02-22-nextjs-migration-design.md` (in the sga-wordpress repo — copy relevant sections to the new repo's docs/)

---

## Phase 1: Foundation

### Task 1: Create Repo and Next.js Project

**Files:**
- Create: entire project scaffold

**Step 1: Create the GitHub repo**

```bash
cd ~/repos
mkdir sga-website && cd sga-website
git init
git remote add origin https://github.com/dsayed/sga-website.git
```

Create the repo on GitHub first (private, no template, no README).

**Step 2: Scaffold Next.js**

```bash
npx create-next-app@latest . --typescript --tailwind --eslint --app --src-dir --no-import-alias --turbopack
```

Answers to prompts:
- TypeScript: Yes
- ESLint: Yes
- Tailwind CSS: Yes
- `src/` directory: Yes
- App Router: Yes
- Turbopack: Yes
- Import alias: No (use default `@/`)

**Step 3: Verify it runs**

```bash
npm run dev
```

Open http://localhost:3000 — you should see the Next.js starter page.

**Step 4: Clean up the starter**

Remove all default content from `src/app/page.tsx` and `src/app/globals.css`. Replace `page.tsx` with:

```tsx
export default function Home() {
  return (
    <main>
      <h1>Saving Great Animals</h1>
      <p>The right dog for the right home — since 2007</p>
    </main>
  );
}
```

Replace `src/app/layout.tsx` metadata:

```tsx
export const metadata: Metadata = {
  title: "Saving Great Animals",
  description: "Seattle-based dog rescue. Finding the right dog for the right home since 2007.",
};
```

**Step 5: Add .nvmrc and .node-version**

Create `.nvmrc` with content `22` (LTS — don't use 25 for production, Vercel supports 22).

**Step 6: Create CLAUDE.md**

Create `CLAUDE.md` with project context:

```markdown
# SGA Website

Public website for Saving Great Animals, a Seattle-based dog rescue.

## Stack
- Next.js 15 (App Router), React 19, TypeScript
- Supabase (Postgres, Auth, Storage)
- Tailwind CSS 4
- Vercel (hosting)

## Commands
- `npm run dev` — local dev server (localhost:3000)
- `npm run build` — production build
- `npm run lint` — ESLint
- `npx supabase start` — local Supabase (requires Docker)
- `npx supabase db push` — push migrations to remote
- `npx supabase gen types typescript --local > src/lib/database.types.ts` — regenerate types

## Key Directories
- `src/app/` — pages (App Router)
- `src/components/` — shared components
- `src/lib/` — Supabase client, utilities, types
- `supabase/migrations/` — SQL migrations (version-controlled)
- `public/` — static assets (logo, favicon)

## Design Doc
- `docs/plans/2026-02-22-nextjs-migration-design.md`

## Git
- Repo: github.com/dsayed/sga-website
- Identity: dsayed
```

**Step 7: Commit and push**

```bash
git add -A
git commit -m "feat: scaffold Next.js 15 project with TypeScript and Tailwind"
git branch -M main
git push -u origin main
```

---

### Task 2: Supabase Project Setup

**Files:**
- Create: `supabase/config.toml`
- Create: `src/lib/supabase/client.ts`
- Create: `src/lib/supabase/server.ts`
- Create: `.env.local`

**Step 1: Create Supabase project**

Go to https://supabase.com/dashboard and create a new project:
- Name: `sga-website`
- Region: West US (closest to Seattle)
- Generate a strong database password — save it in 1Password as "SGA Website - Supabase"

Note the project URL and anon key from Settings → API.

**Step 2: Initialize Supabase locally**

```bash
npx supabase init
```

This creates `supabase/config.toml`.

**Step 3: Link to remote project**

```bash
npx supabase link --project-ref <project-ref-from-dashboard>
```

**Step 4: Install Supabase packages**

```bash
npm install @supabase/supabase-js @supabase/ssr
```

**Step 5: Create environment variables**

Create `.env.local`:

```
NEXT_PUBLIC_SUPABASE_URL=https://<project-ref>.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=<anon-key-from-dashboard>
```

Add `.env.local` to `.gitignore` (should already be there from Next.js scaffold).

Create `.env.example` (committed) with placeholder values:

```
NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key
```

**Step 6: Create Supabase client helpers**

Create `src/lib/supabase/client.ts`:

```typescript
import { createBrowserClient } from "@supabase/ssr";

export function createClient() {
  return createBrowserClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
  );
}
```

Create `src/lib/supabase/server.ts`:

```typescript
import { createServerClient } from "@supabase/ssr";
import { cookies } from "next/headers";

export async function createClient() {
  const cookieStore = await cookies();

  return createServerClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
    {
      cookies: {
        getAll() {
          return cookieStore.getAll();
        },
        setAll(cookiesToSet) {
          try {
            cookiesToSet.forEach(({ name, value, options }) =>
              cookieStore.set(name, value, options),
            );
          } catch {
            // Called from Server Component — ignore
          }
        },
      },
    },
  );
}
```

Create `src/lib/supabase/middleware.ts`:

```typescript
import { createServerClient } from "@supabase/ssr";
import { NextResponse, type NextRequest } from "next/server";

export async function updateSession(request: NextRequest) {
  let supabaseResponse = NextResponse.next({ request });

  const supabase = createServerClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
    {
      cookies: {
        getAll() {
          return request.cookies.getAll();
        },
        setAll(cookiesToSet) {
          cookiesToSet.forEach(({ name, value }) =>
            request.cookies.set(name, value),
          );
          supabaseResponse = NextResponse.next({ request });
          cookiesToSet.forEach(({ name, value, options }) =>
            supabaseResponse.cookies.set(name, value, options),
          );
        },
      },
    },
  );

  await supabase.auth.getUser();

  return supabaseResponse;
}
```

Create `src/middleware.ts`:

```typescript
import { type NextRequest } from "next/server";
import { updateSession } from "@/lib/supabase/middleware";

export async function middleware(request: NextRequest) {
  return await updateSession(request);
}

export const config = {
  matcher: [
    "/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)",
  ],
};
```

**Step 7: Verify build passes**

```bash
npm run build
```

**Step 8: Commit**

```bash
git add -A
git commit -m "feat: add Supabase client setup with SSR support"
```

---

### Task 3: Database Schema — Core Tables

**Files:**
- Create: `supabase/migrations/001_core_schema.sql`
- Create: `src/lib/database.types.ts` (generated)

**Step 1: Write the migration**

Create `supabase/migrations/001_core_schema.sql`:

```sql
-- Urgency enum for foster dogs
create type foster_urgency as enum ('urgent', 'needed', 'secured');

-- Profiles table (extends Supabase auth.users)
create table profiles (
  id uuid references auth.users on delete cascade primary key,
  full_name text,
  role text not null default 'editor' check (role in ('admin', 'editor')),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- Foster dogs
create table foster_dogs (
  id uuid default gen_random_uuid() primary key,
  name text not null,
  breed text,
  age text,
  urgency foster_urgency not null default 'needed',
  notes text,
  photo_path text,
  sort_order int not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- Events
create table events (
  id uuid default gen_random_uuid() primary key,
  title text not null,
  date_start timestamptz not null,
  date_end timestamptz,
  location text,
  description text,
  external_url text,
  photo_path text,
  published boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- Sponsors
create table sponsors (
  id uuid default gen_random_uuid() primary key,
  name text not null,
  logo_path text,
  url text,
  sort_order int not null default 0,
  active boolean not null default true
);

-- Editable page content
create table page_content (
  id uuid default gen_random_uuid() primary key,
  page_slug text not null,
  section_key text not null,
  content text not null default '',
  updated_at timestamptz not null default now(),
  updated_by uuid references auth.users,
  unique (page_slug, section_key)
);

-- Navigation links
create table nav_links (
  id uuid default gen_random_uuid() primary key,
  label text not null,
  url text not null,
  position text not null check (position in ('header', 'footer', 'mobile_bar')),
  sort_order int not null default 0,
  active boolean not null default true
);

-- Auto-update updated_at timestamps
create or replace function update_updated_at()
returns trigger as $$
begin
  new.updated_at = now();
  return new;
end;
$$ language plpgsql;

create trigger foster_dogs_updated_at before update on foster_dogs
  for each row execute function update_updated_at();

create trigger events_updated_at before update on events
  for each row execute function update_updated_at();

create trigger page_content_updated_at before update on page_content
  for each row execute function update_updated_at();

create trigger profiles_updated_at before update on profiles
  for each row execute function update_updated_at();

-- Auto-create profile on signup
create or replace function handle_new_user()
returns trigger as $$
begin
  insert into public.profiles (id, full_name)
  values (new.id, new.raw_user_meta_data->>'full_name');
  return new;
end;
$$ language plpgsql security definer;

create trigger on_auth_user_created after insert on auth.users
  for each row execute function handle_new_user();
```

**Step 2: Apply migration locally**

```bash
npx supabase start  # starts local Supabase (requires Docker)
npx supabase db push --local
```

**Step 3: Push migration to remote**

```bash
npx supabase db push
```

**Step 4: Generate TypeScript types**

```bash
npx supabase gen types typescript --local > src/lib/database.types.ts
```

**Step 5: Commit**

```bash
git add supabase/migrations/001_core_schema.sql src/lib/database.types.ts
git commit -m "feat: add core database schema — foster dogs, events, sponsors, pages, nav"
```

---

### Task 4: Row Level Security Policies

**Files:**
- Create: `supabase/migrations/002_rls_policies.sql`

**Step 1: Write the RLS migration**

Create `supabase/migrations/002_rls_policies.sql`:

```sql
-- Enable RLS on all tables
alter table profiles enable row level security;
alter table foster_dogs enable row level security;
alter table events enable row level security;
alter table sponsors enable row level security;
alter table page_content enable row level security;
alter table nav_links enable row level security;

-- Helper: check if current user is staff (admin or editor)
create or replace function is_staff()
returns boolean as $$
begin
  return exists (
    select 1 from profiles
    where id = auth.uid()
    and role in ('admin', 'editor')
  );
end;
$$ language plpgsql security definer;

-- Helper: check if current user is admin
create or replace function is_admin()
returns boolean as $$
begin
  return exists (
    select 1 from profiles
    where id = auth.uid()
    and role = 'admin'
  );
end;
$$ language plpgsql security definer;

-- Profiles: users can read own profile, admins can read all
create policy "Users can view own profile"
  on profiles for select using (auth.uid() = id);
create policy "Admins can view all profiles"
  on profiles for select using (is_admin());
create policy "Admins can update profiles"
  on profiles for update using (is_admin());

-- Foster dogs: public read, staff write
create policy "Anyone can view foster dogs"
  on foster_dogs for select using (true);
create policy "Staff can insert foster dogs"
  on foster_dogs for insert with check (is_staff());
create policy "Staff can update foster dogs"
  on foster_dogs for update using (is_staff());
create policy "Staff can delete foster dogs"
  on foster_dogs for delete using (is_staff());

-- Events: public read (published only), staff write
create policy "Anyone can view published events"
  on events for select using (published = true or is_staff());
create policy "Staff can insert events"
  on events for insert with check (is_staff());
create policy "Staff can update events"
  on events for update using (is_staff());
create policy "Staff can delete events"
  on events for delete using (is_staff());

-- Sponsors: public read (active only), staff write
create policy "Anyone can view active sponsors"
  on sponsors for select using (active = true or is_staff());
create policy "Staff can insert sponsors"
  on sponsors for insert with check (is_staff());
create policy "Staff can update sponsors"
  on sponsors for update using (is_staff());
create policy "Staff can delete sponsors"
  on sponsors for delete using (is_staff());

-- Page content: public read, staff write
create policy "Anyone can view page content"
  on page_content for select using (true);
create policy "Staff can insert page content"
  on page_content for insert with check (is_staff());
create policy "Staff can update page content"
  on page_content for update using (is_staff());

-- Nav links: public read (active only), admin write
create policy "Anyone can view active nav links"
  on nav_links for select using (active = true or is_admin());
create policy "Admins can insert nav links"
  on nav_links for insert with check (is_admin());
create policy "Admins can update nav links"
  on nav_links for update using (is_admin());
create policy "Admins can delete nav links"
  on nav_links for delete using (is_admin());

-- Storage bucket for images
insert into storage.buckets (id, name, public) values ('images', 'images', true);

create policy "Anyone can view images"
  on storage.objects for select using (bucket_id = 'images');
create policy "Staff can upload images"
  on storage.objects for insert with check (bucket_id = 'images' and is_staff());
create policy "Staff can update images"
  on storage.objects for update using (bucket_id = 'images' and is_staff());
create policy "Staff can delete images"
  on storage.objects for delete using (bucket_id = 'images' and is_staff());
```

**Step 2: Apply locally and push**

```bash
npx supabase db push --local
npx supabase db push
```

**Step 3: Commit**

```bash
git add supabase/migrations/002_rls_policies.sql
git commit -m "feat: add RLS policies — public read, role-based write"
```

---

### Task 5: Auth — Login and Session

**Files:**
- Create: `src/app/login/page.tsx`
- Create: `src/app/auth/callback/route.ts`
- Create: `src/components/auth/login-form.tsx`
- Create: `src/lib/supabase/auth.ts`

**Step 1: Configure Google OAuth in Supabase dashboard**

In Supabase Dashboard → Authentication → Providers → Google:
- Enable Google provider
- Create OAuth credentials at console.cloud.google.com
- Set authorized redirect URI to: `https://<project-ref>.supabase.co/auth/v1/callback`
- Paste Client ID and Secret into Supabase dashboard
- Save credentials in 1Password as "SGA Website - Google OAuth"

Also enable Email provider (already enabled by default in Supabase).

**Step 2: Create auth helper**

Create `src/lib/supabase/auth.ts`:

```typescript
import { createClient } from "./server";

export async function getUser() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  return user;
}

export async function getUserProfile() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return null;

  const { data: profile } = await supabase
    .from("profiles")
    .select("*")
    .eq("id", user.id)
    .single();

  return profile ? { ...user, ...profile } : null;
}

export async function isStaff() {
  const profile = await getUserProfile();
  return profile?.role === "admin" || profile?.role === "editor";
}

export async function isAdmin() {
  const profile = await getUserProfile();
  return profile?.role === "admin";
}
```

**Step 3: Create OAuth callback route**

Create `src/app/auth/callback/route.ts`:

```typescript
import { createClient } from "@/lib/supabase/server";
import { NextResponse } from "next/server";

export async function GET(request: Request) {
  const { searchParams, origin } = new URL(request.url);
  const code = searchParams.get("code");
  const next = searchParams.get("next") ?? "/";

  if (code) {
    const supabase = await createClient();
    const { error } = await supabase.auth.exchangeCodeForSession(code);
    if (!error) {
      return NextResponse.redirect(`${origin}${next}`);
    }
  }

  return NextResponse.redirect(`${origin}/login?error=auth`);
}
```

**Step 4: Create login page**

Create `src/components/auth/login-form.tsx`:

```tsx
"use client";

import { createClient } from "@/lib/supabase/client";
import { useState } from "react";

export function LoginForm() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [mode, setMode] = useState<"password" | "magic">("password");

  const supabase = createClient();

  async function signInWithGoogle() {
    await supabase.auth.signInWithOAuth({
      provider: "google",
      options: { redirectTo: `${window.location.origin}/auth/callback` },
    });
  }

  async function signInWithEmail() {
    setLoading(true);
    setError(null);

    if (mode === "magic") {
      const { error } = await supabase.auth.signInWithOtp({ email });
      if (error) setError(error.message);
      else setError("Check your email for a login link.");
    } else {
      const { error } = await supabase.auth.signInWithPassword({
        email,
        password,
      });
      if (error) setError(error.message);
      else window.location.href = "/";
    }

    setLoading(false);
  }

  return (
    <div className="mx-auto max-w-sm space-y-6">
      <button
        onClick={signInWithGoogle}
        className="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 font-medium text-gray-700 hover:bg-gray-50"
      >
        Continue with Google
      </button>

      <div className="relative">
        <div className="absolute inset-0 flex items-center">
          <div className="w-full border-t border-gray-300" />
        </div>
        <div className="relative flex justify-center text-sm">
          <span className="bg-[#FBF8F4] px-2 text-gray-500">or</span>
        </div>
      </div>

      <div className="space-y-4">
        <input
          type="email"
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="w-full rounded-lg border border-gray-300 px-4 py-3"
        />

        {mode === "password" && (
          <input
            type="password"
            placeholder="Password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="w-full rounded-lg border border-gray-300 px-4 py-3"
          />
        )}

        <button
          onClick={signInWithEmail}
          disabled={loading}
          className="w-full rounded-lg bg-[#2B3990] px-4 py-3 font-medium text-white hover:bg-[#232e73]"
        >
          {loading
            ? "..."
            : mode === "magic"
              ? "Send Magic Link"
              : "Sign In"}
        </button>

        <button
          onClick={() => setMode(mode === "password" ? "magic" : "password")}
          className="w-full text-sm text-[#2B3990] hover:underline"
        >
          {mode === "password"
            ? "Use magic link instead"
            : "Use password instead"}
        </button>
      </div>

      {error && (
        <p className="text-center text-sm text-red-600">{error}</p>
      )}
    </div>
  );
}
```

Create `src/app/login/page.tsx`:

```tsx
import { LoginForm } from "@/components/auth/login-form";
import { getUser } from "@/lib/supabase/auth";
import { redirect } from "next/navigation";

export default async function LoginPage() {
  const user = await getUser();
  if (user) redirect("/");

  return (
    <main className="flex min-h-screen items-center justify-center bg-[#FBF8F4] px-4">
      <div className="w-full max-w-sm space-y-8">
        <div className="text-center">
          <h1 className="font-heading text-2xl font-bold text-[#2B3990]">
            Staff Login
          </h1>
          <p className="mt-2 text-gray-600">
            Saving Great Animals team access
          </p>
        </div>
        <LoginForm />
      </div>
    </main>
  );
}
```

**Step 5: Verify login page renders**

```bash
npm run dev
```

Open http://localhost:3000/login — should show the login form with Google button and email fields.

**Step 6: Commit**

```bash
git add -A
git commit -m "feat: add auth — login page with Google, email/password, and magic link"
```

---

### Task 6: Brand System — Tailwind Config, Fonts, Logo

**Files:**
- Create: `src/app/fonts.ts`
- Modify: `src/app/globals.css`
- Modify: `src/app/layout.tsx`
- Create: `public/logo.svg` (download from live site)
- Create: `public/favicon.ico`

**Step 1: Install Google Fonts**

Next.js has built-in font optimization. Create `src/app/fonts.ts`:

```typescript
import { DM_Sans } from "next/font/google";
import { Fraunces } from "next/font/google";

export const dmSans = DM_Sans({
  subsets: ["latin"],
  variable: "--font-body",
});

export const fraunces = Fraunces({
  subsets: ["latin"],
  variable: "--font-heading",
});
```

**Step 2: Configure Tailwind with brand tokens**

Replace `src/app/globals.css`:

```css
@import "tailwindcss";

@theme {
  --color-blue: #2B3990;
  --color-blue-dark: #232e73;
  --color-orange: #E8772B;
  --color-orange-dark: #d06520;
  --color-warm-white: #FBF8F4;
  --color-warm-gray: #F3F0EC;

  --font-body: var(--font-body);
  --font-heading: var(--font-heading);
}

body {
  font-family: var(--font-body), sans-serif;
  background-color: var(--color-warm-white);
  color: #1a1a1a;
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading), serif;
}
```

**Step 3: Update root layout**

Update `src/app/layout.tsx`:

```tsx
import type { Metadata } from "next";
import { dmSans, fraunces } from "./fonts";
import "./globals.css";

export const metadata: Metadata = {
  title: {
    default: "Saving Great Animals",
    template: "%s | Saving Great Animals",
  },
  description:
    "Seattle-based dog rescue. Finding the right dog for the right home since 2007.",
  metadataBase: new URL("https://savinggreatanimals.org"),
  openGraph: {
    type: "website",
    locale: "en_US",
    siteName: "Saving Great Animals",
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={`${dmSans.variable} ${fraunces.variable}`}>
      <body>{children}</body>
    </html>
  );
}
```

**Step 4: Download logo from live site**

Download the SGA logo from savinggreatanimals.org and save as `public/logo.svg` (or `public/logo.png` if SVG isn't available). Also create a favicon from the logo.

**Step 5: Verify fonts and colors render**

```bash
npm run dev
```

Open http://localhost:3000 — heading should use Fraunces, body text should use DM Sans, background should be warm white (#FBF8F4).

**Step 6: Commit**

```bash
git add -A
git commit -m "feat: add brand system — SGA colors, fonts, logo"
```

---

### Task 7: Layout Components — Header, Footer, Mobile Bar

**Files:**
- Create: `src/components/layout/header.tsx`
- Create: `src/components/layout/footer.tsx`
- Create: `src/components/layout/mobile-bar.tsx`
- Create: `src/components/layout/site-layout.tsx`
- Modify: `src/app/layout.tsx`

**Step 1: Create header**

Create `src/components/layout/header.tsx`:

The header should have:
- SGA logo (left)
- Navigation links: Adopt, Foster, Get Involved, Events, About (center/hidden on mobile)
- Donate button (right, always visible)
- Hamburger menu (mobile)
- If logged in: small "Admin" link or pencil icon

Use the brand colors. Mobile-first: logo + hamburger + donate on small screens. Full nav on desktop.

**Step 2: Create footer**

Create `src/components/layout/footer.tsx`:

Three columns:
- Col 1: "Saving Great Animals" + tagline
- Col 2: Quick Links (Adopt, Foster, Volunteer, Donate, Events)
- Col 3: Contact (email, location, Facebook/Instagram links)
- Bottom: 501(c)(3) notice with EIN 80-0323640

**Step 3: Create mobile bottom bar**

Create `src/components/layout/mobile-bar.tsx`:

Persistent bottom bar on mobile only (hidden on `md:` and up):
- Two buttons: Adopt, Donate
- Semi-transparent background with backdrop blur
- Fixed to bottom of viewport

**Step 4: Create site layout wrapper**

Create `src/components/layout/site-layout.tsx`:

```tsx
import { Header } from "./header";
import { Footer } from "./footer";
import { MobileBar } from "./mobile-bar";

export function SiteLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <Header />
      <main className="min-h-screen pb-16 md:pb-0">{children}</main>
      <Footer />
      <MobileBar />
    </>
  );
}
```

**Step 5: Wire into root layout**

Update `src/app/layout.tsx` to wrap children in `<SiteLayout>`.

**Step 6: Verify on mobile and desktop**

Open http://localhost:3000 at 390px wide (mobile) and 1280px wide (desktop). Check:
- Mobile: logo, hamburger, donate button, bottom bar visible
- Desktop: full nav, no bottom bar

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: add header, footer, and mobile bottom bar"
```

---

### Task 8: Seed Content from Live Site

**Files:**
- Create: `supabase/seed.sql`
- Create: `scripts/seed-content.ts` (optional — or just use seed.sql)

**Step 1: Write seed SQL with real content**

Create `supabase/seed.sql` with page content from savinggreatanimals.org:

```sql
-- Homepage
insert into page_content (page_slug, section_key, content) values
  ('home', 'hero_title', '8,500+ dogs homed since 2007'),
  ('home', 'hero_subtitle', 'Seattle''s dog rescue — finding the right dog for the right home'),
  ('home', 'partners_heading', 'Our Partners'),
  ('home', 'partners_text', 'Saving Great Animals is a 501(c)(3) nonprofit organization, rescuing dogs in the Seattle area since 2007.');

-- About page
insert into page_content (page_slug, section_key, content) values
  ('about', 'hero_title', 'About Saving Great Animals'),
  ('about', 'hero_subtitle', 'The right dog for the right home — since 2007'),
  ('about', 'main_body', 'Saving Great Animals is a matchmaking rescue organization focused mainly on dogs in the Greater Seattle area. We work tirelessly to match the best pet to your family based on breed, lifestyle, and other factors. With more than 8,500 lovingly homed since 2007, we are proud of our dedicated team and foster homes for bringing new life to pets with loving homes to last their lifetime.

We adopt out only after a dog has been spayed or neutered, updated on shots, received proper medical care, and been chipped. We are dedicated to lowering the dog reproduction population, which leads to millions of lost lives.

We use a trial adoption program, which includes training and counsel, and dogs are only adopted after that period. As a result, our return rates are very low. We love every single animal we rescue and we are cradle to grave, staying in touch with adopting families for years, often adding new furry loves to their homes.

Adopters must sign a contract to ensure that if for some unforeseen reason the dog needs to be rehomed, he or she is returned to Saving Great Animals for rehoming. Our dogs are never to see a high-kill shelter again in their lifetime.

We are a federally recognized 501(c)(3) nonprofit (EIN: 80-0323640), relying solely on adoption fees, donations, and grants.'),
  ('about', 'cta_heading', 'Ready to make a difference?'),
  ('about', 'cta_text', 'Your support helps us rescue more dogs in the Seattle area.');

-- Foster page
insert into page_content (page_slug, section_key, content) values
  ('foster', 'hero_title', 'Open your home, change a life'),
  ('foster', 'hero_subtitle', 'SGA fosters save 300+ dogs a year — and we need your help'),
  ('foster', 'intro_heading', 'Foster homes are desperately needed'),
  ('foster', 'intro_body', '**Foster families are needed now more than ever!** Dogs in Washington state are in crisis. Shelters are experiencing massive overcrowding and rescues are operating far over capacity. Intakes at animal control facilities have reached an all-time high, while adoptions are at an all-time low. This leads to a sharp increase in euthanasia rates. Having access to foster homes means we can save lives!

All you need to be a foster is the ability to provide a home for a dog for a few weeks or more. We will match you with a dog suited to your preferences and provide guidance and support along the way. While the dog is staying with you, SGA takes care of any vet bills and you provide the food and bedding. **Fostering does not cost you a penny!**

Fostering can be done around your schedule! Many of our fosters work full time, so don''t worry if your foster dog needs to hang out on their own during the day.

Often people worry that it will be hard to let their foster dog go. Some fosters do find it challenging, but there is nothing more rewarding than seeing a dog whose life would otherwise have ended tragically go home with a family full of joy.'),
  ('foster', 'faq_1_q', 'What does a foster parent do?'),
  ('foster', 'faq_1_a', 'Foster parenting is a wonderful way to enjoy the love and attention of a dog without making a permanent commitment. Because we don''t have a facility, SGA''s foster program is one of the most crucial elements of our rescue. By taking a dog into your home, you are allowing it to become accustomed to a safe and loving home life.'),
  ('foster', 'faq_2_q', 'Do I get to choose which dog I foster?'),
  ('foster', 'faq_2_a', 'Yes! Whether you''re looking for a mellow companion or an active ball-chasing dog you can take on jogs, our goal is to place a dog with you that fits your needs and lifestyle.'),
  ('foster', 'faq_3_q', 'What if the dog doesn''t work out for me?'),
  ('foster', 'faq_3_a', 'If at any time the fit isn''t working, we will be happy to take the dog back and try a different one. Just like people, dogs all have different personalities, and our goal is to help find the right match.'),
  ('foster', 'faq_4_q', 'Who pays for veterinary care?'),
  ('foster', 'faq_4_a', 'SGA gives all foster parents a list of approved veterinarians and pays for all veterinary fees and inoculations for foster dogs.'),
  ('foster', 'faq_5_q', 'How will the dog find a permanent home while with me?'),
  ('foster', 'faq_5_a', 'SGA requires the dog to be available for a meet-and-greet with approved applicants. Once an application is received, an adoption coordinator will connect you with the applicant to set up an appointment.'),
  ('foster', 'faq_6_q', 'I have kids — can I still foster?'),
  ('foster', 'faq_6_a', 'Dogs who live with very young children need higher tolerance levels. Because we can''t always guarantee tolerance with all ages, we can only approve foster families with children over 6 years of age.'),
  ('foster', 'cta_heading', 'Ready to start fostering?'),
  ('foster', 'cta_text', 'Fill out our application or email us with any questions at info@savinggreatanimals.org');

-- Adopt page
insert into page_content (page_slug, section_key, content) values
  ('adopt', 'hero_title', 'Find your new best friend'),
  ('adopt', 'hero_subtitle', 'Dogs available for adoption in the Seattle area'),
  ('adopt', 'process_body', 'Our adoption process starts with an application. We''ll reach out within 24-48 hours. Most dogs spend time in foster homes, so you''ll meet them in a home setting. After a two-week trial adoption to evaluate fit, we finalize the adoption with a fee and contract.

**Adoption fees:**
- Adult dogs (over 1 year): $450
- Puppies (under 1 year): $525
- Puppies born in rescue care: $595

Fees cover initial vaccinations, deworming, microchip, and spay/neuter.');

-- Donate page
insert into page_content (page_slug, section_key, content) values
  ('donate', 'hero_title', 'Support our mission'),
  ('donate', 'hero_subtitle', 'Your donations make rescue possible'),
  ('donate', 'main_body', 'Your donations make it possible for the dogs in our care to receive the medical care, housing and behavioral support they need. Saving Great Animals is a 501(c)(3) non-profit (EIN: 80-0323640). All donations are tax-deductible.'),
  ('donate', 'bark_buddies_heading', 'Become a Bark Buddy'),
  ('donate', 'bark_buddies_text', 'Join our monthly giving program to provide year-round, lifesaving care for dogs who need us most. Monthly donors are our most vital supporters.');

-- Get Involved / Ways to Help page
insert into page_content (page_slug, section_key, content) values
  ('get-involved', 'hero_title', 'Ways to Help'),
  ('get-involved', 'hero_subtitle', 'Every bit of support saves lives'),
  ('get-involved', 'main_body', '**Donate** — Give directly via our donation page. All donations are tax-deductible.

**Bark Buddies** — Monthly recurring gifts provide year-round, lifesaving care.

**Volunteer** — We are always busy and can always use extra hands! Contact Lily at lily@savinggreatanimals.org to get started. Monthly orientation sessions available.

**Foster a dog** — The most direct way to save a life. We provide all vet care.

**Attend an event** — Join us at the annual Bark Benefit and other fundraisers.

**Chewy Wish List** — Purchase items we need from our Chewy wish list.

**Donate a vehicle** — Through our CareEasy partnership at careasy.org/nonprofit/saving-great-animals or call 855-500-7438.

**Shop SGA merch** — Visit our shop at sga-shop.fourthwall.com.');

-- Events page
insert into page_content (page_slug, section_key, content) values
  ('events', 'hero_title', 'Events'),
  ('events', 'hero_subtitle', 'Join us at upcoming SGA events');

-- Resources page
insert into page_content (page_slug, section_key, content) values
  ('resources', 'hero_title', 'Resources'),
  ('resources', 'hero_subtitle', 'Helpful resources for dog owners'),
  ('resources', 'main_body', 'Training resources, veterinary partners, lost pet guidance, and end-of-life support — coming soon.');

-- Surrender page
insert into page_content (page_slug, section_key, content) values
  ('surrender', 'hero_title', 'Surrender a Dog'),
  ('surrender', 'hero_subtitle', 'We''re here to help when you can''t keep your dog');

-- Navigation
insert into nav_links (label, url, position, sort_order) values
  ('Adopt', '/adopt', 'header', 1),
  ('Foster', '/foster', 'header', 2),
  ('Get Involved', '/get-involved', 'header', 3),
  ('Events', '/events', 'header', 4),
  ('About', '/about', 'header', 5),
  ('Merch', 'https://sga-shop.fourthwall.com', 'header', 6),
  ('Adopt', '/adopt', 'mobile_bar', 1),
  ('Donate', '/donate', 'mobile_bar', 2),
  ('Available Dogs', '/adopt', 'footer', 1),
  ('Become a Foster', '/foster', 'footer', 2),
  ('Volunteer', '/get-involved', 'footer', 3),
  ('Donate', '/donate', 'footer', 4),
  ('Events', '/events', 'footer', 5);
```

**Step 2: Apply seed data**

```bash
npx supabase db reset --local  # resets and re-applies migrations + seed
```

**Step 3: Push seed to remote**

For the remote Supabase instance, run the seed SQL via the Supabase SQL Editor in the dashboard, or:

```bash
npx supabase db push
# Then run seed via dashboard SQL Editor
```

**Step 4: Commit**

```bash
git add supabase/seed.sql
git commit -m "feat: seed real page content from savinggreatanimals.org"
```

---

### Task 9: Static Pages with Content from Database

**Files:**
- Create: `src/lib/content.ts`
- Create: `src/app/(public)/layout.tsx`
- Create: `src/app/(public)/page.tsx` (homepage)
- Create: `src/app/(public)/about/page.tsx`
- Create: `src/app/(public)/foster/page.tsx`
- Create: `src/app/(public)/adopt/page.tsx`
- Create: `src/app/(public)/donate/page.tsx`
- Create: `src/app/(public)/get-involved/page.tsx`
- Create: `src/app/(public)/events/page.tsx`
- Create: `src/components/ui/page-hero.tsx`
- Create: `src/components/ui/section.tsx`

**Step 1: Create content helper**

Create `src/lib/content.ts`:

```typescript
import { createClient } from "@/lib/supabase/server";

export async function getPageContent(pageSlug: string) {
  const supabase = await createClient();
  const { data } = await supabase
    .from("page_content")
    .select("section_key, content")
    .eq("page_slug", pageSlug);

  const content: Record<string, string> = {};
  data?.forEach((row) => {
    content[row.section_key] = row.content;
  });
  return content;
}
```

**Step 2: Create shared UI components**

Create `src/components/ui/page-hero.tsx` — a full-width hero section with dark blue background, white text, title and subtitle. Responsive — larger text on desktop, smaller on mobile.

Create `src/components/ui/section.tsx` — a constrained-width content section with consistent padding.

**Step 3: Create the public route group layout**

Create `src/app/(public)/layout.tsx` that wraps children in `<SiteLayout>`. Move the homepage to `src/app/(public)/page.tsx`.

**Step 4: Build each page**

Each page follows the same pattern:
1. Call `getPageContent('page-slug')` in the server component
2. Render a `<PageHero>` with `content.hero_title` and `content.hero_subtitle`
3. Render the body sections using the content values
4. Export metadata (title, description) for SEO

Build pages in this order: About (simplest), Foster (has FAQ), Donate, Get Involved, Events (placeholder for now), Adopt (placeholder — RescueGroups comes in Phase 3), Homepage (references other pages).

**Step 5: Verify all pages render**

```bash
npm run dev
```

Visit each page and verify content loads from the database.

**Step 6: Commit**

```bash
git add -A
git commit -m "feat: add static pages with content from Supabase"
```

---

### Task 10: Deploy to Vercel

**Files:**
- Create: `vercel.json` (if needed)

**Step 1: Deploy**

```bash
vercel
```

Follow prompts:
- Link to existing project: No
- Project name: sga-website
- Root directory: ./
- Auto-detected framework: Next.js

**Step 2: Set environment variables in Vercel**

```bash
vercel env add NEXT_PUBLIC_SUPABASE_URL production
vercel env add NEXT_PUBLIC_SUPABASE_ANON_KEY production
```

**Step 3: Deploy to production**

```bash
vercel --prod
```

**Step 4: Verify the live site**

Open the Vercel URL and check all pages render correctly.

**Step 5: Commit any config changes**

```bash
git add -A
git commit -m "chore: add Vercel deployment config"
```

---

## Phase 2: Foster Dogs + Events

### Task 11: Foster Dogs — Data Layer and Public Display

**Files:**
- Create: `src/lib/foster-dogs.ts`
- Create: `src/app/(public)/foster-dog/[slug]/page.tsx`
- Create: `src/components/foster/dog-card.tsx`
- Create: `src/components/foster/dog-grid.tsx`
- Modify: `src/app/(public)/foster/page.tsx`

**Step 1: Create data helper**

Create `src/lib/foster-dogs.ts`:

```typescript
import { createClient } from "@/lib/supabase/server";

export async function getFosterDogs(includeSecured = false) {
  const supabase = await createClient();
  let query = supabase
    .from("foster_dogs")
    .select("*")
    .order("urgency", { ascending: true })
    .order("sort_order", { ascending: true });

  if (!includeSecured) {
    query = query.neq("urgency", "secured");
  }

  const { data } = await query;
  return data ?? [];
}

export async function getFosterDog(id: string) {
  const supabase = await createClient();
  const { data } = await supabase
    .from("foster_dogs")
    .select("*")
    .eq("id", id)
    .single();
  return data;
}
```

**Step 2: Create dog card component**

Create `src/components/foster/dog-card.tsx` — a card with:
- Dog photo (from Supabase Storage URL or placeholder)
- Name
- Urgency badge (orange for urgent, blue for needed, green for secured)
- Breed and age
- Notes (truncated on card)
- Clickable — links to `/foster-dog/[id]`
- Hover shadow effect

**Step 3: Create dog grid component**

Create `src/components/foster/dog-grid.tsx` — responsive grid of dog cards. `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3` with gap.

**Step 4: Add foster dogs section to foster page**

Modify `src/app/(public)/foster/page.tsx` to fetch and display foster dogs between the intro content and FAQ.

**Step 5: Create foster dog detail page**

Create `src/app/(public)/foster-dog/[id]/page.tsx`:
- Large photo
- Name as h1
- Urgency badge
- Breed, age
- Full notes
- "Apply to Foster" button (links to secure.savinggreatanimals.org)
- "Back to Foster Page" link
- SEO metadata with dog name and description

**Step 6: Seed foster dog data**

Add to `supabase/seed.sql` — migrate the 10 foster dogs from the WordPress site with their real data (Aiden, Binky, Cattle Dog Mix Puppies, Chihuahua Mix Puppies, Roxy, Miss Piggy, etc.).

Upload their photos to Supabase Storage `images/foster-dogs/` bucket.

**Step 7: Verify**

Visit `/foster` — dogs should appear in a grid, sorted by urgency. Click a card — should navigate to detail page.

**Step 8: Commit**

```bash
git add -A
git commit -m "feat: add foster dogs — grid, cards, detail page"
```

---

### Task 12: Foster Dogs — Staff Editing

**Files:**
- Create: `src/app/(admin)/admin/dogs/page.tsx`
- Create: `src/app/(admin)/admin/dogs/new/page.tsx`
- Create: `src/app/(admin)/admin/dogs/[id]/edit/page.tsx`
- Create: `src/app/(admin)/layout.tsx`
- Create: `src/components/foster/dog-form.tsx`
- Create: `src/app/api/foster-dogs/route.ts`
- Create: `src/app/api/foster-dogs/[id]/route.ts`
- Create: `src/components/auth/staff-guard.tsx`

**Step 1: Create admin layout with auth guard**

Create `src/app/(admin)/layout.tsx` that checks if the user is staff and redirects to `/login` if not. Shows a minimal admin nav bar.

Create `src/components/auth/staff-guard.tsx` — server component that checks auth and renders children or redirects.

**Step 2: Create dog form component**

Create `src/components/foster/dog-form.tsx` — a reusable form for creating/editing foster dogs:
- Name (text input)
- Breed (text input)
- Age (text input)
- Urgency (select: Urgent, Needed, Secured)
- Notes (textarea)
- Photo (file upload to Supabase Storage)
- Save / Cancel buttons

Mobile-first: full-width inputs, large touch targets.

**Step 3: Create API routes**

Create `src/app/api/foster-dogs/route.ts`:
- GET: list all dogs (used by client components)
- POST: create new dog (staff only)

Create `src/app/api/foster-dogs/[id]/route.ts`:
- PUT: update dog
- DELETE: delete dog

All routes verify the user is staff via Supabase auth before allowing writes.

**Step 4: Create admin pages**

- `/admin/dogs` — table of all foster dogs with status badges, edit/delete buttons
- `/admin/dogs/new` — dog form for creating
- `/admin/dogs/[id]/edit` — dog form pre-filled for editing

**Step 5: Add inline edit buttons on public pages**

Modify `src/components/foster/dog-card.tsx` and the foster page to show a small pencil icon on each card when the user is staff. Pencil links to `/admin/dogs/[id]/edit`.

Add "Add Dog" button at the top of the foster dogs section (visible to staff only).

**Step 6: Add ISR revalidation**

When a foster dog is created/updated/deleted via the API routes, call `revalidatePath('/foster')` and `revalidatePath('/foster-dog/[id]')` so the static pages update.

**Step 7: Test the full flow**

1. Log in as staff
2. Go to `/admin/dogs` — see all dogs
3. Click "Add Dog" — fill form, upload photo, save
4. Go to `/foster` — new dog appears
5. Click edit icon on a card — update the dog
6. Verify changes appear on public page

**Step 8: Commit**

```bash
git add -A
git commit -m "feat: add foster dog admin — CRUD, photo upload, inline edit buttons"
```

---

### Task 13: Events — Data Layer and Public Display

**Files:**
- Create: `src/lib/events.ts`
- Create: `src/components/events/event-card.tsx`
- Create: `src/components/events/event-list.tsx`
- Modify: `src/app/(public)/events/page.tsx`

**Step 1: Create data helper**

Create `src/lib/events.ts` — fetch published events, sorted by date (upcoming first).

**Step 2: Create event card and list components**

Event card shows: title, date (formatted), location, description excerpt, optional photo, external link button ("RSVP" or "Sign Up").

Event list: vertical stack of cards, grouped into "Upcoming" and "Past".

**Step 3: Update events page**

Modify events page to fetch and display events from Supabase.

**Step 4: Seed an event**

Add The Bark Benefit to `supabase/seed.sql`:

```sql
insert into events (title, date_start, location, description, external_url, published) values
  ('The Bark Benefit', '2026-09-25 18:00:00-07', 'Hyatt Regency Bellevue, Bellevue WA', 'Join us for our annual fundraiser celebrating the dogs and people of Saving Great Animals.', 'https://secure.savinggreatanimals.org', true);
```

**Step 5: Verify**

Visit `/events` — should show The Bark Benefit with date, location, and RSVP link.

**Step 6: Commit**

```bash
git add -A
git commit -m "feat: add events page with upcoming/past events"
```

---

### Task 14: Events — Staff Editing

**Files:**
- Create: `src/app/(admin)/admin/events/page.tsx`
- Create: `src/app/(admin)/admin/events/new/page.tsx`
- Create: `src/app/(admin)/admin/events/[id]/edit/page.tsx`
- Create: `src/components/events/event-form.tsx`
- Create: `src/app/api/events/route.ts`
- Create: `src/app/api/events/[id]/route.ts`

**Step 1: Create event form component**

Fields: title, start date/time, end date/time (optional), location, description (textarea with markdown support), external URL (optional), photo upload, published toggle.

**Step 2: Create API routes**

Same pattern as foster dogs — GET, POST, PUT, DELETE with staff auth check and ISR revalidation.

**Step 3: Create admin pages**

- `/admin/events` — list of events with published status, edit/delete
- `/admin/events/new` — create form
- `/admin/events/[id]/edit` — edit form

**Step 4: Add inline edit buttons on public events page**

Pencil icon on each event card, "Add Event" button at top — visible to staff only.

**Step 5: Test the full flow**

1. Log in as staff
2. Create an event via `/admin/events/new`
3. Verify it appears on `/events`
4. Edit it inline
5. Toggle published off — verify it disappears from public view

**Step 6: Commit**

```bash
git add -A
git commit -m "feat: add events admin — CRUD, publish toggle, inline editing"
```

---

### Task 15: Homepage with Real Content

**Files:**
- Modify: `src/app/(public)/page.tsx`
- Create: `src/components/home/hero.tsx`
- Create: `src/components/home/action-tiles.tsx`
- Create: `src/components/home/foster-spotlight.tsx`

**Step 1: Create homepage hero**

Full-viewport hero with:
- Background image (real SGA dog photo — download from live site)
- Dark overlay
- "8,500+ dogs homed since 2007" (from page_content)
- Subtitle (from page_content)
- Three CTA buttons: Adopt a Dog, Foster a Dog, How to Help

**Step 2: Create action tiles**

Three cards in a row (stacking on mobile): Adopt, Foster, Get Involved. Each with a background image, title, subtitle, and CTA button. Images from live site.

**Step 3: Create foster dog spotlight**

Shows 3 most urgent foster dogs with "X dogs need foster homes" heading. Links to foster page.

**Step 4: Assemble homepage**

```tsx
export default async function Home() {
  const content = await getPageContent("home");
  const dogs = await getFosterDogs();
  const urgentCount = dogs.filter((d) => d.urgency !== "secured").length;

  return (
    <>
      <Hero content={content} />
      <ActionTiles />
      <FosterSpotlight dogs={dogs.slice(0, 3)} count={urgentCount} />
    </>
  );
}
```

**Step 5: Download hero images from live site**

Download hero photos from savinggreatanimals.org and upload to Supabase Storage `images/heroes/` bucket. Reference them in the components.

**Step 6: Verify on mobile and desktop**

Check the homepage looks good at 390px and 1280px. Hero image visible, CTAs tappable, action tiles stack on mobile, foster spotlight shows real dogs.

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: build homepage with hero, action tiles, and foster spotlight"
```

---

### Task 16: JSON-LD Structured Data

**Files:**
- Create: `src/components/seo/json-ld.tsx`
- Modify: `src/app/layout.tsx`
- Modify: foster dog detail page
- Modify: events page

**Step 1: Create Organization schema**

Add to root layout — appears on every page:

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Saving Great Animals",
  "url": "https://savinggreatanimals.org",
  "logo": "https://savinggreatanimals.org/logo.png",
  "description": "Seattle-based 501(c)(3) nonprofit dog rescue. Finding the right dog for the right home since 2007.",
  "areaServed": "Seattle metropolitan area",
  "address": { "@type": "PostalAddress", "addressLocality": "Seattle", "addressRegion": "WA" },
  "sameAs": [
    "https://www.facebook.com/SGADogRescue/",
    "https://www.instagram.com/savinggreatanimals/"
  ]
}
```

**Step 2: Add Event schema to events**

Each published event gets an Event JSON-LD block with name, startDate, location, description, url.

**Step 3: Add AnimalShelter schema to adopt page**

```json
{
  "@context": "https://schema.org",
  "@type": "AnimalShelter",
  "name": "Saving Great Animals",
  "areaServed": "Seattle metropolitan area"
}
```

**Step 4: Commit**

```bash
git add -A
git commit -m "feat: add JSON-LD structured data for SEO and GEO"
```

---

### Task 17: Final Deploy and Verify

**Step 1: Build locally**

```bash
npm run build
```

Fix any build errors.

**Step 2: Deploy to Vercel**

```bash
git push origin main
```

Vercel auto-deploys.

**Step 3: Verify all pages on production**

Check every page on the Vercel URL:
- Homepage: hero, tiles, foster spotlight
- About: content, CTA
- Foster: content, FAQ, foster dog grid, clickable cards
- Foster dog detail: photo, info, CTAs
- Adopt: placeholder with adoption info
- Donate: content, Bark Buddies section
- Get Involved: ways to help
- Events: event cards
- Login: form renders
- Admin (when logged in): dogs list, events list

**Step 4: Test editing flow on production**

1. Create a test staff account in Supabase dashboard
2. Log in on the live site
3. Add a test foster dog
4. Verify it appears on the public foster page
5. Edit it
6. Delete it

**Step 5: Commit any fixes**

```bash
git add -A
git commit -m "fix: production deploy fixes"
```

---

## What Comes Next (Phases 3-4, separate plans)

**Phase 3: Full Feature Parity**
- RescueGroups API integration (Available Dogs page)
- Sponsors management (replaces Canva collage)
- Nav link management
- Page content inline editing
- Social media integration (OG tags, share buttons, embeds)

**Phase 4: Cutover**
- Review with Lily and Jacintha
- Point savinggreatanimals.org DNS to Vercel
- Shut down WordPress on Railway and GoDaddy
- Update Google Business Profile
