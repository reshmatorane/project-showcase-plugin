import { useState, useEffect } from 'react';

/**
 * Custom hook to debounce rapid value updates (300-400ms).
 *
 * @param value Raw input value.
 * @param delay Milliseconds to delay before updating (default: 350ms).
 * @returns Debounced value.
 */
export function useDebounce<T>(value: T, delay: number = 350): T {
  const [debouncedValue, setDebouncedValue] = useState<T>(value);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
}
