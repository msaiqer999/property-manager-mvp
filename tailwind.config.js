import forms from '@tailwindcss/forms';

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/View/**/*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [forms],
};
