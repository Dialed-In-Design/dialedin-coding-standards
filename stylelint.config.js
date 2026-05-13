// Base Stylelint config for Dialed In Design WordPress projects.
//
// Downstream usage. In the consumer repo:
//
//   // stylelint.config.js
//   module.exports = {
//       extends: ['@dialed-in-design/coding-standards/stylelint.config.js'],
//       // project-specific overrides go here
//   };

module.exports = {
    extends: ['@wordpress/stylelint-config'],
    ignoreFiles: [
        'vendor/**',
        'node_modules/**',
        'dist/**',
        'build/**',
        '**/*.min.css',
    ],
};
