import forms from '@tailwindcss/forms';

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/View/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    primary: 'rgb(var(--brand-primary-rgb) / <alpha-value>)',
                    'primary-hover': 'rgb(var(--brand-primary-hover-rgb) / <alpha-value>)',
                    'primary-soft': 'var(--brand-primary-soft)',
                    accent: 'rgb(var(--brand-accent-rgb) / <alpha-value>)',
                    'accent-soft': 'var(--brand-accent-soft)',
                    background: 'rgb(var(--brand-background-rgb) / <alpha-value>)',
                    surface: 'rgb(var(--brand-surface-rgb) / <alpha-value>)',
                    border: 'rgb(var(--brand-border-rgb) / <alpha-value>)',
                    text: 'rgb(var(--brand-text-rgb) / <alpha-value>)',
                    muted: 'rgb(var(--brand-muted-rgb) / <alpha-value>)',
                    'muted-soft': 'var(--brand-muted-soft)',
                    overlay: 'var(--brand-overlay)',
                },
                state: {
                    success: 'rgb(var(--state-success-rgb) / <alpha-value>)',
                    'success-soft': 'var(--state-success-soft)',
                    warning: 'rgb(var(--state-warning-rgb) / <alpha-value>)',
                    'warning-soft': 'var(--state-warning-soft)',
                    danger: 'rgb(var(--state-danger-rgb) / <alpha-value>)',
                    'danger-soft': 'var(--state-danger-soft)',
                    info: 'rgb(var(--state-info-rgb) / <alpha-value>)',
                    'info-soft': 'var(--state-info-soft)',
                },
            },
        },
    },
    plugins: [forms],
};
