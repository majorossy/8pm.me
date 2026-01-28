'use client';

import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { User, Session, AuthError } from '@supabase/supabase-js';
import { supabase, isSupabaseConfigured, Profile } from '@/lib/supabase';

interface AuthContextType {
  user: User | null;
  profile: Profile | null;
  session: Session | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  isConfigured: boolean;
  signUp: (email: string, password: string) => Promise<{ error: AuthError | null }>;
  signIn: (email: string, password: string) => Promise<{ error: AuthError | null }>;
  signInWithMagicLink: (email: string) => Promise<{ error: AuthError | null }>;
  signOut: () => Promise<void>;
  updateProfile: (updates: Partial<Profile>) => Promise<{ error: Error | null }>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [profile, setProfile] = useState<Profile | null>(null);
  const [session, setSession] = useState<Session | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const isConfigured = isSupabaseConfigured();

  // Fetch user profile from database
  const fetchProfile = useCallback(async (userId: string) => {
    if (!isConfigured) return null;

    try {
      const { data, error } = await supabase
        .from('profiles')
        .select('*')
        .eq('id', userId)
        .single();

      if (error) {
        console.error('Error fetching profile:', error);
        return null;
      }

      return data as Profile;
    } catch (error) {
      console.error('Error fetching profile:', error);
      return null;
    }
  }, [isConfigured]);

  // Create profile if it doesn't exist
  const createProfile = useCallback(async (user: User) => {
    if (!isConfigured) return null;

    try {
      const { data, error } = await supabase
        .from('profiles')
        .upsert({
          id: user.id,
          display_name: user.email?.split('@')[0] || 'User',
          avatar_url: null,
          created_at: new Date().toISOString(),
        })
        .select()
        .single();

      if (error) {
        console.error('Error creating profile:', error);
        return null;
      }

      return data as Profile;
    } catch (error) {
      console.error('Error creating profile:', error);
      return null;
    }
  }, [isConfigured]);

  // Initialize auth state
  useEffect(() => {
    if (!isConfigured) {
      setIsLoading(false);
      return;
    }

    // Get initial session
    supabase.auth.getSession().then(async ({ data: { session } }) => {
      setSession(session);
      setUser(session?.user ?? null);

      if (session?.user) {
        let userProfile = await fetchProfile(session.user.id);
        if (!userProfile) {
          userProfile = await createProfile(session.user);
        }
        setProfile(userProfile);
      }

      setIsLoading(false);
    });

    // Listen for auth changes
    const {
      data: { subscription },
    } = supabase.auth.onAuthStateChange(async (event, session) => {
      setSession(session);
      setUser(session?.user ?? null);

      if (session?.user) {
        let userProfile = await fetchProfile(session.user.id);
        if (!userProfile && event === 'SIGNED_IN') {
          userProfile = await createProfile(session.user);
        }
        setProfile(userProfile);
      } else {
        setProfile(null);
      }
    });

    return () => subscription.unsubscribe();
  }, [isConfigured, fetchProfile, createProfile]);

  const signUp = useCallback(async (email: string, password: string) => {
    if (!isConfigured) {
      return { error: { message: 'Supabase not configured' } as AuthError };
    }

    const { error } = await supabase.auth.signUp({
      email,
      password,
      options: {
        emailRedirectTo: typeof window !== 'undefined' ? window.location.origin : undefined,
      },
    });

    return { error };
  }, [isConfigured]);

  const signIn = useCallback(async (email: string, password: string) => {
    if (!isConfigured) {
      return { error: { message: 'Supabase not configured' } as AuthError };
    }

    const { error } = await supabase.auth.signInWithPassword({
      email,
      password,
    });

    return { error };
  }, [isConfigured]);

  const signInWithMagicLink = useCallback(async (email: string) => {
    if (!isConfigured) {
      return { error: { message: 'Supabase not configured' } as AuthError };
    }

    const { error } = await supabase.auth.signInWithOtp({
      email,
      options: {
        emailRedirectTo: typeof window !== 'undefined' ? window.location.origin : undefined,
      },
    });

    return { error };
  }, [isConfigured]);

  const signOut = useCallback(async () => {
    if (!isConfigured) return;

    await supabase.auth.signOut();
    setUser(null);
    setProfile(null);
    setSession(null);
  }, [isConfigured]);

  const updateProfile = useCallback(async (updates: Partial<Profile>) => {
    if (!isConfigured || !user) {
      return { error: new Error('Not authenticated') };
    }

    try {
      const { error } = await supabase
        .from('profiles')
        .update(updates)
        .eq('id', user.id);

      if (error) {
        return { error: new Error(error.message) };
      }

      // Refresh profile
      const newProfile = await fetchProfile(user.id);
      if (newProfile) {
        setProfile(newProfile);
      }

      return { error: null };
    } catch (error) {
      return { error: error as Error };
    }
  }, [isConfigured, user, fetchProfile]);

  return (
    <AuthContext.Provider
      value={{
        user,
        profile,
        session,
        isLoading,
        isAuthenticated: !!user,
        isConfigured,
        signUp,
        signIn,
        signInWithMagicLink,
        signOut,
        updateProfile,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
