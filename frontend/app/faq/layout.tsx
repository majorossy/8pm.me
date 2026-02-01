import { Metadata } from 'next';
import StructuredData from '@/components/StructuredData';

export const metadata: Metadata = {
  title: 'FAQ - Frequently Asked Questions',
  description: 'Find answers to common questions about 8pm.me, the free live music streaming platform. Learn about recordings, downloads, accounts, and more.',
  alternates: {
    canonical: '/faq',
  },
};

// FAQ data for schema (must match the client component)
const faqItems = [
  {
    question: 'Is 8pm.me really free?',
    answer: 'Yes! 8pm.me is completely free to use. All recordings come from Archive.org, which hosts legally shareable live music. There are no subscriptions, no ads, and no hidden fees.',
  },
  {
    question: 'Where do the recordings come from?',
    answer: 'All recordings are hosted on Archive.org, a non-profit digital library dedicated to preserving cultural artifacts. The live music collection includes thousands of shows recorded by fans (tapers) and shared with permission from the artists.',
  },
  {
    question: 'Can I download shows for offline listening?',
    answer: 'While 8pm.me is designed for streaming, you can visit Archive.org directly to download complete shows in various formats (MP3, FLAC, etc.). Each show page includes a link to the original Archive.org recording.',
  },
  {
    question: 'Are these recordings legal?',
    answer: 'Yes! All artists featured on 8pm.me allow or encourage taping and sharing of their live performances. This is a long-standing tradition in the jam band community that helps spread the music and build fan communities.',
  },
  {
    question: 'Do I need to create an account?',
    answer: 'No account is required for basic browsing and listening. However, creating an account lets you save playlists, track your listening history, and sync your library across devices.',
  },
  {
    question: 'Can I share shows with friends?',
    answer: 'Absolutely! Every show and playlist has a shareable link. You can also share directly to social media. Remember our ethos: please copy freely — never sell.',
  },
  {
    question: 'How can I contribute or report issues?',
    answer: 'We welcome feedback! If you notice missing shows, incorrect metadata, or technical issues, please contact us. This is a student project and we\'re always looking to improve.',
  },
  {
    question: 'Why is it called "8PM"?',
    answer: '8PM represents the magic hour when most concerts begin — that moment of anticipation before the lights dim and the music starts. It\'s our tribute to the live music experience.',
  },
];

// Generate FAQPage schema
const faqSchema = {
  '@context': 'https://schema.org',
  '@type': 'FAQPage',
  mainEntity: faqItems.map((item) => ({
    '@type': 'Question',
    name: item.question,
    acceptedAnswer: {
      '@type': 'Answer',
      text: item.answer,
    },
  })),
};

export default function FAQLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <StructuredData data={faqSchema} />
      {children}
    </>
  );
}
