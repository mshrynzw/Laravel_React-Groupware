import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Input } from './Input';

describe('Input', () => {
  it('ラベルと入力欄を表示する', () => {
    render(<Input label="メール" type="email" />);
    expect(screen.getByText('メール')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toHaveAttribute('type', 'email');
  });

  it('入力できる', async () => {
    const user = userEvent.setup();
    render(<Input label="名前" />);
    const field = screen.getByRole('textbox');
    await user.type(field, '太郎');
    expect(field).toHaveValue('太郎');
  });
});
