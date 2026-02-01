# 8PM Launch CEO Task List

## Overview
Comprehensive task list for launching 8PM as a production service.

**Audit completed:** February 1, 2026
**Full audit report:** `docs/LAUNCH_AUDIT.md`

---

## 🔧 Infrastructure & Hosting

- [ ] **AWS Setup**
  - [ ] Choose region (us-east-1 recommended for Archive.org proximity)
  - [ ] Set up VPC, subnets, security groups
  - [ ] EC2 or ECS for Magento backend
  - [ ] RDS for MariaDB (or Aurora)
  - [ ] ElastiCache for Redis
  - [ ] OpenSearch Service
  - [ ] S3 for media/static assets
  - [ ] CloudFront CDN (critical for audio streaming performance)

- [ ] **Domain & DNS**
  - [ ] Purchase/configure 8pm.me domain
  - [ ] Route 53 or Cloudflare DNS
  - [ ] SSL certificates (ACM or Let's Encrypt)

- [ ] **Environments**
  - [ ] Production environment

---

## 📊 Analytics & SEO

- [ ] **Google Analytics 4**
  - [ ] Create GA4 property
  - [x] ~~Add tracking code to frontend~~ (CODE EXISTS - needs `NEXT_PUBLIC_GA_MEASUREMENT_ID`)
  - [x] ~~Set up conversion events~~ (40+ events defined in `lib/analytics.ts`)

- [ ] **Google Search Console**
  - [ ] Verify domain ownership
  - [ ] Submit sitemap
  - [ ] Monitor indexing

- [x] **SEO Implementation** (reference: docs/seo-implementation/) - **COMPLETE**
  - [x] ~~Dynamic meta titles/descriptions~~ (All pages via `lib/seo.ts`)
  - [x] ~~Sitemap generation~~ (`app/sitemap.ts` - 35k+ URLs with ISR)
  - [x] ~~robots.txt~~ (`app/robots.ts` - blocks AI scrapers)
  - [x] ~~Schema.org structured data~~ (MusicGroup, Album, Recording, Event, FAQ)

---

## 📧 Email & Communication

- [ ] **Email Setup**
  - [ ] admin@8pm.me
  - [ ] support@8pm.me
  - [ ] noreply@8pm.me (transactional)

- [ ] **Email Service Provider**
  - [ ] AWS SES or SendGrid for transactional email
  - [ ] Email templates (welcome, password reset, etc.)

---

## ⚖️ Legal & Compliance

- [x] **Required Pages** - ALL COMPLETE
  - [x] ~~Privacy Policy~~ (`/privacy` - comprehensive, Jan 29, 2026)
  - [x] ~~Terms of Service~~ (`/terms` - comprehensive, Jan 30, 2026)
  - [x] ~~DMCA/Copyright Policy~~ (`/dmca` - comprehensive, Feb 1, 2026)
  - [x] ~~Cookie Policy~~ (`/cookie-policy` - comprehensive, Feb 1, 2026)

- [ ] **Compliance**
  - [x] ~~Cookie consent banner (GDPR)~~ (`CookieConsentBanner.tsx` - Feb 1, 2026)
  - [ ] Age verification if needed
  - [x] ~~Archive.org attribution requirements~~ (Footer logo + multiple pages)
  - [ ] Artist/taper community guidelines

---

## 🛡️ Security

- [ ] **Infrastructure Security**
  - [ ] WAF (AWS WAF or Cloudflare)
  - [ ] DDoS protection
  - [x] ~~Rate limiting on API~~ (Feb 1, 2026 - `lib/rateLimit.ts`)
  - [ ] Secrets management (AWS Secrets Manager)

- [ ] **Application Security**
  - [x] ~~Security headers (CSP, HSTS, etc.)~~ (Added Feb 1, 2026 - nginx.conf + next.config.js)
  - [x] ~~Input validation audit~~ (Feb 1, 2026 - lib/validation.ts + forms updated)
  - [ ] Dependency vulnerability scan

- [ ] **Backup & Recovery**
  - [ ] Automated database backups
  - [ ] Backup retention policy
  - [ ] Disaster recovery plan

---

## 📈 Monitoring & Operations

- [ ] **Error Tracking**
  - [ ] Sentry or Bugsnag for frontend/backend errors
  - [x] ~~Error boundary component~~ (exists, needs analytics wiring)
  - [x] ~~Core Web Vitals monitoring~~ (`WebVitalsMonitor.tsx` active)

- [ ] **Uptime Monitoring**
  - [ ] Pingdom, UptimeRobot, or AWS CloudWatch
  - [ ] Status page (optional: statuspage.io)

- [ ] **Logging**
  - [ ] CloudWatch Logs or ELK stack
  - [ ] Log retention policy

- [ ] **Alerting**
  - [ ] PagerDuty or Opsgenie (or simple email alerts)
  - [ ] Alert thresholds defined

---

## 🎨 Branding & Social

- [ ] **Brand Assets**
  - [ ] Logo (various sizes, formats) - **MISSING**
  - [x] ~~Favicon.ico~~ (Feb 1, 2026 - .ico, .svg, 16px, 32px PNG)
  - [x] ~~Open Graph default image~~ (Feb 1, 2026 - og-default.jpg 1200x630)
  - [x] ~~App icons (PWA/mobile)~~ (9 sizes, 72-512px)

- [ ] **Social Media**
  - [ ] Twitter/X account
  - [ ] Instagram account
  - [ ] Reddit presence (r/gratefuldead, r/jambands, etc.)

---

## 💳 Monetization (Future)

- [ ] **Payment Processing** (if adding premium features)
  - [ ] Stripe integration
  - [ ] Subscription tiers defined

- [ ] **Donations/Tips** (alternative model)
  - [ ] Buy Me a Coffee or similar

---

## 🚀 Launch Prep

- [ ] **Testing**
  - [ ] Load testing (can handle traffic spikes?)
  - [ ] Cross-browser testing
  - [ ] Mobile testing
  - [ ] Accessibility audit (a11y)

- [ ] **Soft Launch**
  - [ ] Beta user group
  - [ ] Feedback collection mechanism

- [ ] **Launch Announcement**
  - [ ] Press release or blog post
  - [ ] Reddit announcements
  - [ ] Jam band forum posts

---

## 📱 Mobile (Future Phase)

- [x] **PWA Enhancement** - **MOSTLY COMPLETE**
  - [x] ~~Offline support~~ (Service worker with caching)
  - [x] ~~Install prompts~~ (iOS + Android support)

- [ ] **Native Apps** (optional)
  - [ ] iOS App Store
  - [ ] Google Play Store

---

## Implementation Priority (Based on Audit)

### CRITICAL (Before Any Production) - ALL COMPLETE
1. [x] ~~Cookie consent banner component~~ (Feb 1, 2026)
2. [x] ~~Cookie policy page (`/cookie-policy`)~~ (Feb 1, 2026)
3. [x] ~~Security headers~~ (Feb 1, 2026 - nginx.conf + next.config.js)
4. [x] ~~Rate limiting on API routes~~ (Feb 1, 2026 - `lib/rateLimit.ts`)
5. [x] ~~DMCA policy page (`/dmca`)~~ (Feb 1, 2026)
6. [x] ~~GA4 now conditional on cookie consent~~ (Feb 1, 2026)

### HIGH (Before Public Launch)
7. [x] ~~Create og-default.jpg (1200x630px)~~ (Feb 1, 2026)
8. [x] ~~Add favicon.ico~~ (Feb 1, 2026 - .ico, .svg, 16px, 32px)
9. [x] ~~Wire analytics tracking to components~~ (Feb 1, 2026 - search, artist, album pages)
10. [x] ~~Input validation improvements~~ (Feb 1, 2026 - lib/validation.ts + forms)
11. [x] ~~CSP header implementation~~ (Feb 1, 2026 - PWA support added)
12. [ ] Domain purchase (8pm.me)

### INFRASTRUCTURE (Production Deployment)
13. [ ] AWS VPC/Network setup
14. [ ] RDS for MariaDB
15. [ ] ElastiCache for Redis
16. [ ] CloudFront CDN
17. [ ] SSL certificates (ACM)
18. [ ] CI/CD pipeline

### OPERATIONS (Post-Launch)
19. [ ] Sentry integration
20. [ ] Uptime monitoring
21. [ ] Automated backups
22. [ ] Alerting rules

---

## Audit Summary

| Category | Grade | Status |
|----------|-------|--------|
| SEO | A- (94%) | Production-ready |
| Analytics | B | Code complete, needs config |
| Legal | C+ | Privacy/Terms done, Cookie/DMCA missing |
| Security | D | Significant gaps |
| PWA | B+ (85%) | Core complete |
| Infrastructure | N/A | Not started |

---

## Notes

- Archive.org content is free/open - focus on attribution, not licensing fees
- Taper community is important - engage authentically
- Start simple, iterate based on user feedback
- **Full audit details:** `docs/LAUNCH_AUDIT.md`
