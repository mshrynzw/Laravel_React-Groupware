import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Button } from './Button';

describe('Button', () => {
  it('子要素を表示する', () => {
    render(<Button>送信</Button>);
    expect(screen.getByRole('button', { name: '送信' })).toBeInTheDocument();
  });
});
