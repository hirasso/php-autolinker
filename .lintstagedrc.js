// @ts-check

/**
 * @type {import('lint-staged').Configuration}
 */
export default {
  "src/**/*.php": ["composer analyse", "composer format"],
};
