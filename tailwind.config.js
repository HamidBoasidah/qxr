module.exports = {
	darkMode: 'class',
	content: [
		'./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
		'./storage/framework/views/*.php',
		'./resources/**/*.blade.php',
		'./resources/**/*.js',
		'./resources/**/*.vue',
	],
	theme: {
    extend: {
        fontFamily: {
            sans: ['Figtree', ...require('tailwindcss/defaultTheme').fontFamily.sans],
        },
        colors: {
            testcolor: '#ff0000'
            }
        }
    },
	plugins: [
		require('tailwindcss-rtl'),
		require('@tailwindcss/forms'),
	],
}
