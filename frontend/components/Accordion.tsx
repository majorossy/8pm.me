'use client';

import { useState, useCallback, createContext, useContext, useId, KeyboardEvent } from 'react';
import { motion, AnimatePresence } from 'framer-motion';

// Chevron icon that rotates
const ChevronIcon = ({ isOpen }: { isOpen: boolean }) => (
  <motion.svg
    className="w-5 h-5 text-[#8a8478]"
    fill="none"
    stroke="currentColor"
    viewBox="0 0 24 24"
    initial={false}
    animate={{ rotate: isOpen ? 180 : 0 }}
    transition={{ duration: 0.2 }}
  >
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
  </motion.svg>
);

// Context for AccordionGroup to manage open state
interface AccordionGroupContextType {
  openId: string | null;
  setOpenId: (id: string | null) => void;
  allowMultiple: boolean;
  reducedMotion: boolean;
}

const AccordionGroupContext = createContext<AccordionGroupContextType | null>(null);

// Hook to check for reduced motion preference
function useReducedMotion(): boolean {
  if (typeof window === 'undefined') return false;
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

// AccordionGroup manages multiple accordions
interface AccordionGroupProps {
  children: React.ReactNode;
  allowMultiple?: boolean;
  defaultOpenId?: string | null;
}

export function AccordionGroup({
  children,
  allowMultiple = false,
  defaultOpenId = null
}: AccordionGroupProps) {
  const [openId, setOpenId] = useState<string | null>(defaultOpenId);
  const reducedMotion = useReducedMotion();

  return (
    <AccordionGroupContext.Provider value={{ openId, setOpenId, allowMultiple, reducedMotion }}>
      <div className="space-y-2" role="region" aria-label="Accordion group">
        {children}
      </div>
    </AccordionGroupContext.Provider>
  );
}

// Individual Accordion item
interface AccordionProps {
  id?: string;
  title: string;
  children: React.ReactNode;
  defaultOpen?: boolean;
  icon?: React.ReactNode;
}

export function Accordion({
  id: providedId,
  title,
  children,
  defaultOpen = false,
  icon
}: AccordionProps) {
  const generatedId = useId();
  const id = providedId || generatedId;
  const groupContext = useContext(AccordionGroupContext);

  // Standalone mode (no group context)
  const [standaloneOpen, setStandaloneOpen] = useState(defaultOpen);

  // Determine if this accordion is open
  const isInGroup = groupContext !== null;
  const isOpen = isInGroup
    ? (groupContext.allowMultiple ? standaloneOpen : groupContext.openId === id)
    : standaloneOpen;

  const reducedMotion = isInGroup ? groupContext.reducedMotion : useReducedMotion();

  const toggle = useCallback(() => {
    if (isInGroup && !groupContext.allowMultiple) {
      groupContext.setOpenId(isOpen ? null : id);
    } else {
      setStandaloneOpen(prev => !prev);
    }
  }, [isInGroup, groupContext, isOpen, id]);

  const handleKeyDown = useCallback((e: KeyboardEvent<HTMLButtonElement>) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggle();
    }
  }, [toggle]);

  const headerId = `accordion-header-${id}`;
  const panelId = `accordion-panel-${id}`;

  return (
    <div className="border border-[#3a3632] rounded-lg overflow-hidden bg-[#2a2825]">
      <button
        id={headerId}
        aria-expanded={isOpen}
        aria-controls={panelId}
        onClick={toggle}
        onKeyDown={handleKeyDown}
        className="w-full flex items-center justify-between gap-3 p-4 text-left hover:bg-[#32302c] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d4a060] focus-visible:ring-inset"
      >
        <div className="flex items-center gap-3">
          {icon && <span className="text-[#d4a060] flex-shrink-0">{icon}</span>}
          <h3 className="text-lg font-semibold text-[#d4a060]">{title}</h3>
        </div>
        <ChevronIcon isOpen={isOpen} />
      </button>

      <AnimatePresence initial={false}>
        {isOpen && (
          <motion.div
            id={panelId}
            role="region"
            aria-labelledby={headerId}
            initial={reducedMotion ? { height: 'auto', opacity: 1 } : { height: 0, opacity: 0 }}
            animate={{ height: 'auto', opacity: 1 }}
            exit={reducedMotion ? { height: 'auto', opacity: 0 } : { height: 0, opacity: 0 }}
            transition={reducedMotion ? { duration: 0.1 } : { duration: 0.2, ease: 'easeInOut' }}
            className="overflow-hidden"
          >
            <div className="px-4 pb-4 text-[#8a8478] leading-relaxed border-t border-[#3a3632]/50">
              <div className="pt-4">
                {children}
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

export default Accordion;
