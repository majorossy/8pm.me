'use client';

import { useEffect, useState, useRef } from 'react';
import { usePathname } from 'next/navigation';

export default function LoadingBar() {
  const pathname = usePathname();
  const [progress, setProgress] = useState(0);
  const [isVisible, setIsVisible] = useState(false);
  const prevPathname = useRef(pathname);
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);
  const intervalRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    const clearTimers = () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
    };

    // Pathname changed - start loading animation
    if (pathname !== prevPathname.current) {
      clearTimers();
      prevPathname.current = pathname;

      // Quick start
      setProgress(0);
      setIsVisible(true);

      // Animate to 30% quickly
      requestAnimationFrame(() => {
        setProgress(30);
      });

      // Slow crawl from 30% to 90%
      let currentProgress = 30;
      intervalRef.current = setInterval(() => {
        currentProgress += Math.random() * 10;
        if (currentProgress >= 90) {
          currentProgress = 90;
          if (intervalRef.current) {
            clearInterval(intervalRef.current);
            intervalRef.current = null;
          }
        }
        setProgress(currentProgress);
      }, 500);

      // Complete after a short delay (page should be rendered)
      timeoutRef.current = setTimeout(() => {
        clearTimers();
        setProgress(100);

        // Hide after completion animation
        setTimeout(() => {
          setIsVisible(false);
          setProgress(0);
        }, 200);
      }, 300);
    }

    return clearTimers;
  }, [pathname]);

  if (!isVisible && progress === 0) {
    return null;
  }

  return (
    <div
      className="fixed top-0 left-0 right-0 z-[9999] h-[4px] pointer-events-none"
      style={{
        opacity: isVisible ? 1 : 0,
        transition: 'opacity 200ms ease-out',
      }}
    >
      <div
        className="h-full"
        style={{
          width: `${progress}%`,
          background: 'linear-gradient(90deg, #d4a060 0%, #c08a40 100%)',
          boxShadow: '0 0 10px rgba(212, 160, 96, 0.5), 0 0 5px rgba(212, 160, 96, 0.3)',
          transition: progress === 0
            ? 'none'
            : progress <= 30
              ? 'width 100ms ease-out'
              : progress === 100
                ? 'width 150ms ease-out'
                : 'width 500ms ease-out',
        }}
      />
    </div>
  );
}
