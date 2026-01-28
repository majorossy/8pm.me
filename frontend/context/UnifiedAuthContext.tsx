'use client';

import React, { createContext, useContext, useMemo } from 'react';
import { useAuth } from '@/context/AuthContext';
import { useMagentoAuth } from '@/context/MagentoAuthContext';

interface UnifiedUser {
  id: string;
  email: string;
  displayName: string;
  // Source flags
  hasSupabaseAuth: boolean;
  hasMagentoAuth: boolean;
}

interface UnifiedAuthContextType {
  user: UnifiedUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  hasSupabaseAuth: boolean;
  hasMagentoAuth: boolean;
  signOutAll: () => Promise<void>;
}

const UnifiedAuthContext = createContext<UnifiedAuthContextType | null>(null);

export function UnifiedAuthProvider({ children }: { children: React.ReactNode }) {
  const supabaseAuth = useAuth();
  const magentoAuth = useMagentoAuth();

  const hasSupabaseAuth = !!supabaseAuth.user;
  const hasMagentoAuth = !!magentoAuth.customer;
  const isAuthenticated = hasSupabaseAuth || hasMagentoAuth;
  const isLoading = supabaseAuth.isLoading || magentoAuth.isLoading;

  const user = useMemo((): UnifiedUser | null => {
    if (!isAuthenticated) return null;

    // Prefer Supabase user data, fall back to Magento
    if (supabaseAuth.user) {
      return {
        id: supabaseAuth.user.id,
        email: supabaseAuth.user.email || '',
        displayName: supabaseAuth.user.user_metadata?.display_name ||
                     supabaseAuth.user.email?.split('@')[0] || 'User',
        hasSupabaseAuth: true,
        hasMagentoAuth,
      };
    }

    if (magentoAuth.customer) {
      return {
        id: String(magentoAuth.customer.id || magentoAuth.customer.email),
        email: magentoAuth.customer.email,
        displayName: `${magentoAuth.customer.firstname} ${magentoAuth.customer.lastname}`.trim(),
        hasSupabaseAuth: false,
        hasMagentoAuth: true,
      };
    }

    return null;
  }, [supabaseAuth.user, magentoAuth.customer, hasSupabaseAuth, hasMagentoAuth, isAuthenticated]);

  const signOutAll = async () => {
    const promises: Promise<void>[] = [];

    if (hasSupabaseAuth) {
      promises.push(supabaseAuth.signOut());
    }

    if (hasMagentoAuth) {
      promises.push(magentoAuth.signOut());
    }

    await Promise.all(promises);
  };

  return (
    <UnifiedAuthContext.Provider
      value={{
        user,
        isAuthenticated,
        isLoading,
        hasSupabaseAuth,
        hasMagentoAuth,
        signOutAll,
      }}
    >
      {children}
    </UnifiedAuthContext.Provider>
  );
}

export function useUnifiedAuth() {
  const context = useContext(UnifiedAuthContext);
  if (!context) {
    throw new Error('useUnifiedAuth must be used within a UnifiedAuthProvider');
  }
  return context;
}
