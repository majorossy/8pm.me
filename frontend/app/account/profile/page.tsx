'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useMagentoAuth } from '@/context/MagentoAuthContext';

export default function ProfilePage() {
  const router = useRouter();
  const { customer, isAuthenticated, isLoading, refreshCustomer } = useMagentoAuth();
  const [firstname, setFirstname] = useState('');
  const [lastname, setLastname] = useState('');
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.push('/account');
    }
  }, [isLoading, isAuthenticated, router]);

  useEffect(() => {
    if (customer) {
      setFirstname(customer.firstname);
      setLastname(customer.lastname);
    }
  }, [customer]);

  if (isLoading || !customer) {
    return (
      <div className="min-h-screen bg-[#1c1a17] flex items-center justify-center">
        <div className="spinner" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#1c1a17] pb-[140px] md:pb-[90px]">
      <div className="p-6 md:p-8 max-w-2xl mx-auto">
        <Link href="/account" className="text-[#d4a060] hover:underline mb-4 inline-block">
          ← Back to Account
        </Link>
        <h1 className="text-3xl font-bold text-white mb-8">Edit Profile</h1>

        <form onSubmit={(e) => { e.preventDefault(); setMessage({ type: 'success', text: 'Profile update coming soon!' }); }} className="space-y-6">
          <div>
            <label className="block text-white mb-2">Email</label>
            <input
              type="email"
              value={customer.email}
              disabled
              className="w-full bg-[#1c1a17] text-[#8a8478] rounded px-4 py-3 border border-[#3a3632]"
            />
          </div>
          <div>
            <label className="block text-white mb-2">First Name</label>
            <input
              type="text"
              value={firstname}
              onChange={(e) => setFirstname(e.target.value)}
              className="w-full bg-[#2d2a26] text-white rounded px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#d4a060]"
            />
          </div>
          <div>
            <label className="block text-white mb-2">Last Name</label>
            <input
              type="text"
              value={lastname}
              onChange={(e) => setLastname(e.target.value)}
              className="w-full bg-[#2d2a26] text-white rounded px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#d4a060]"
            />
          </div>

          {message && (
            <p className={`text-sm ${message.type === 'success' ? 'text-green-400' : 'text-red-400'}`}>
              {message.text}
            </p>
          )}

          <button
            type="submit"
            disabled={isSaving}
            className="w-full py-3 px-6 rounded-full bg-[#d4a060] text-black font-medium hover:bg-[#c08a40] disabled:opacity-50 transition-colors"
          >
            {isSaving ? 'Saving...' : 'Save Changes'}
          </button>
        </form>
      </div>
    </div>
  );
}
