import js from "@eslint/js";
import globals from "globals";
import tsParser from "@typescript-eslint/parser";
import tsPlugin from "@typescript-eslint/eslint-plugin";

const typecheckedFiles = ["assets/admin/src/**/*.{ts,tsx}"];

export default [
  {
    ignores: [
      "assets/admin/build/**",
      "node_modules/**",
      "vendor/**"
    ]
  },
  js.configs.recommended,
  {
    files: ["assets/admin/src/**/*.{js,jsx}"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "module",
      parserOptions: {
        ecmaFeatures: {
          jsx: true
        }
      },
      globals: {
        ...globals.browser
      }
    },
    rules: {
      "eqeqeq": ["error", "always"],
      "no-console": ["warn", { allow: ["warn", "error"] }],
      "no-duplicate-imports": "error",
      "no-unneeded-ternary": "error",
      "object-shorthand": ["error", "always"],
      "prefer-const": "error"
    }
  },
  {
    files: typecheckedFiles,
    languageOptions: {
      parser: tsParser,
      parserOptions: {
        ecmaVersion: "latest",
        sourceType: "module",
        ecmaFeatures: {
          jsx: true
        },
        project: "./tsconfig.json"
      },
      globals: {
        ...globals.browser
      }
    },
    plugins: {
      "@typescript-eslint": tsPlugin
    },
    rules: {
      "eqeqeq": ["error", "always"],
      "max-depth": ["warn", 4],
      "no-console": ["warn", { allow: ["warn", "error"] }],
      "no-duplicate-imports": "error",
      "no-unused-vars": "off",
      "no-unneeded-ternary": "error",
      "object-shorthand": ["error", "always"],
      "prefer-const": "error",
      "@typescript-eslint/consistent-type-imports": "error",
      "@typescript-eslint/no-explicit-any": "warn",
      "@typescript-eslint/no-unused-vars": [
        "error",
        {
          argsIgnorePattern: "^_",
          varsIgnorePattern: "^_",
          caughtErrorsIgnorePattern: "^_"
        }
      ]
    }
  }
];
