import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./app/View/**/*.php",
        "./resources/**/*.js",
        "./resources/**/*.css",
        "./resources/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                primary: "#00A761", //green
                secondary: "#005073", //dark blue
                accent: "#72A4F4", //light blue
                success: "#16AA39", //pigmanent green
                danger: "#DC3545", //red
                warning: "#FFC107", //yellow abmer
                info: "#2474E3", //blue
                light: "#F8F9FA",
                dark: "#272B35",
                customgray: "#374151",
            },
            fontFamily: {
                // sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                sans: [
                    "Open Sans",
                    "Source Sans Pro",
                    "Nunito",
                    "Arial",
                    "sans-serif",
                ],
                serif: ["Merriweather", "Lora", "serif"],
            },
            borderWidth: {
                3: "3px",
            },
            width: {
                50: "12rem",
                // 96: "24rem",
                100: "25rem",
                120: "35rem",
            },
            height: {
                50: "12rem",
                // 96: "24rem",
                100: "25rem",
                120: "35rem",
            },
            height: {
                50: "12rem",
                // 96: "24rem",
                100: "25rem",
                120: "35rem",
            },
            spacing: {
                20: "5rem",
                22: "5.25rem",
                25: "6rem",
            },
            opacity: {
                50: "0.5",
            },
            pointerEvents: {
                none: "none",
            },
        },
    },

    plugins: [
        forms,
        function ({ addUtilities }) {
            addUtilities({
                ".disabled": {
                    opacity: "0.5",
                    "pointer-events": "none",
                },
            });
        },
    ],
};
