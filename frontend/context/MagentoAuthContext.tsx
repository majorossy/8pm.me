'use client';

import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { MagentoCustomer, MagentoCustomerCreateInput } from '@/lib/types';
import {
  generateCustomerToken,
  createCustomer,
  getCustomer,
  revokeCustomerToken,
  requestPasswordReset,
  getStoredToken,
} from '@/lib/magentoAuth';

interface MagentoAuthContextType {
  customer: MagentoCustomer | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
  signIn: (email: string, password: string) => Promise<boolean>;
  signUp: (input: MagentoCustomerCreateInput) => Promise<boolean>;
  signOut: () => Promise<void>;
  resetPassword: (email: string) => Promise<boolean>;
  refreshCustomer: () => Promise<void>;
}

const MagentoAuthContext = createContext<MagentoAuthContextType | null>(null);

export function MagentoAuthProvider({ children }: { children: React.ReactNode }) {
  const [customer, setCustomer] = useState<MagentoCustomer | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const isAuthenticated = customer !== null;

  // Check for existing token on mount
  useEffect(() => {
    const checkAuth = async () => {
      const token = getStoredToken();
      if (token) {
        try {
          const customerData = await getCustomer(token);
          setCustomer(customerData);
        } catch {
          setCustomer(null);
        }
      }
      setIsLoading(false);
    };
    checkAuth();
  }, []);

  const signIn = useCallback(async (email: string, password: string): Promise<boolean> => {
    setError(null);
    setIsLoading(true);
    try {
      const token = await generateCustomerToken(email, password);
      const customerData = await getCustomer(token);
      setCustomer(customerData);
      return true;
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Sign in failed');
      return false;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const signUp = useCallback(async (input: MagentoCustomerCreateInput): Promise<boolean> => {
    setError(null);
    setIsLoading(true);
    try {
      await createCustomer(input);
      // Auto sign in after registration
      return await signIn(input.email, input.password);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Sign up failed');
      return false;
    } finally {
      setIsLoading(false);
    }
  }, [signIn]);

  const signOut = useCallback(async () => {
    setIsLoading(true);
    try {
      await revokeCustomerToken();
    } finally {
      setCustomer(null);
      setIsLoading(false);
    }
  }, []);

  const resetPassword = useCallback(async (email: string): Promise<boolean> => {
    setError(null);
    try {
      return await requestPasswordReset(email);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Password reset failed');
      return false;
    }
  }, []);

  const refreshCustomer = useCallback(async () => {
    const token = getStoredToken();
    if (token) {
      const customerData = await getCustomer(token);
      setCustomer(customerData);
    }
  }, []);

  return (
    <MagentoAuthContext.Provider
      value={{
        customer,
        isAuthenticated,
        isLoading,
        error,
        signIn,
        signUp,
        signOut,
        resetPassword,
        refreshCustomer,
      }}
    >
      {children}
    </MagentoAuthContext.Provider>
  );
}

export function useMagentoAuth() {
  const context = useContext(MagentoAuthContext);
  if (!context) {
    throw new Error('useMagentoAuth must be used within a MagentoAuthProvider');
  }
  return context;
}
