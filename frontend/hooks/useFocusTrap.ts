import { useEffect, useRef } from 'react';

/**
 * Focus trap hook for modals and dialogs (WCAG 2.1 AA compliance)
 * Traps focus within a container and returns focus to trigger element on close
 */
export function useFocusTrap(isOpen: boolean) {
  const containerRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (!isOpen) return;

    // Store the element that opened the modal
    triggerRef.current = document.activeElement as HTMLElement;

    // Get all focusable elements in the container
    const getFocusableElements = (): HTMLElement[] => {
      if (!containerRef.current) return [];

      const focusableSelectors = [
        'a[href]',
        'button:not([disabled])',
        'textarea:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
      ];

      return Array.from(
        containerRef.current.querySelectorAll<HTMLElement>(focusableSelectors.join(','))
      ).filter((el) => {
        // Filter out hidden elements
        return el.offsetParent !== null;
      });
    };

    // Focus first focusable element
    const focusableElements = getFocusableElements();
    if (focusableElements.length > 0) {
      focusableElements[0].focus();
    }

    // Handle tab key to trap focus
    const handleTab = (e: KeyboardEvent) => {
      if (e.key !== 'Tab') return;

      const focusableElements = getFocusableElements();
      if (focusableElements.length === 0) return;

      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];

      if (e.shiftKey) {
        // Shift + Tab
        if (document.activeElement === firstElement) {
          e.preventDefault();
          lastElement.focus();
        }
      } else {
        // Tab
        if (document.activeElement === lastElement) {
          e.preventDefault();
          firstElement.focus();
        }
      }
    };

    // Add event listener
    document.addEventListener('keydown', handleTab);

    // Cleanup
    return () => {
      document.removeEventListener('keydown', handleTab);

      // Return focus to trigger element
      if (triggerRef.current && typeof triggerRef.current.focus === 'function') {
        triggerRef.current.focus();
      }
    };
  }, [isOpen]);

  return containerRef;
}
