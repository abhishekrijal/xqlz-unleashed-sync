require('dotenv').config();
module.exports = function (grunt) {
	"use strict";

	/**
	 * Files added to WordPress SVN.
	 * @type {Array}
	 */
	var svnFileList = [
		"app/**",
		"src/**",
		"vendor/**",
		"readme.txt",
		"xqlz-unleashed-sync.php"
	];

	/**
	 * Files added to WordPress SVN, don't include 'assets/**' here.
	 * @type {Array}
	 */
	var proFileList = svnFileList.concat(["pro/**"]);

	/**
	 * Initialize grunt.
	 */
	grunt.initConfig({
		pkg: grunt.file.readJSON("package.json"),

		options: {
			text_domain: "xqluz-unleashed",
		},

		env: {
			release: {
				src: ".env",
			},
		},

		watch: {
			scripts: {
				files: "{admin,public}/{css,js}/*{.css,.js}",
				tasks: ["cssmin:build", "babel:build", "uglify:build"],
			},
		},

		clean: {
			build: ["build"],
			release: ["release"],
			pot: ["languages/<%= pkg.name %>.pot"],
			all: [
				"build",
				"release",
				"deploy",
				"assets/public/css/min",
				"assets/public/js/min",
			],
		},

		copy: {
			build: {
				src: [proFileList],
				dest: "build/<%= pkg.name %>/",
				options: {
					process: function (content, srcpath) {
						return content.replace(/(__customizer_prefix)/gm, "rishi__cb")
					},
					noProcess: ['**/*.{png,gif,jpg,jpeg,ico,psd,svg}']
				},
				expand: true,
				dot: true,
			},
		},

		// Minify CSS files.
		cssmin: {
			options: {
				report: "min",
			},
			build: {
				files: [
					{
						expand: true,
						cwd: "assets/public/css",
						src: ["*.css", "!*.min.css"],
						dest: "assets/public/css/min",
						ext: ".min.css",
					},
				],
			},
		},

		babel: {
			options: {
				sourceMap: true,
				presets: ["@babel/preset-env"],
			},
			build: {
				files: {},
			},
		},

		// Minify javascript
		uglify: {
			build: {
				options: {
					mangle: false,
					compress: {
						drop_console: true,
						drop_debugger: true,
					},
					report: ["min"],
				},
				files: {},
			},
		},
		// Compress for release.
		compress: {
			release: {
				options: {
					archive: "release/<%= pkg.name %>-<%= pkg.version %>.zip",
					mode: "zip",
				},
				files: [{ expand: true, cwd: "build/", src: "**" }],
			},
		},

		wp_readme_to_markdown: {
			target: {
				files: {
					"README.md": "readme.txt",
				},
			},
		},

		// Update text domain.
		addtextdomain: {
			options: {
				textdomain: "<%= options.text_domain %>",
				updateDomains: true,
			},
			target: {
				files: {
					src: [
						"*.php",
						"**/*.php",
						"!tests/**",
						"!bin/**",
						"!assets/**",
						"!node_modules/**",
						"!vendor/**",
						"!build/**",
						"!deploy/**",
						"!dist/**",
						"!release/**",
					],
				},
			},
		},
		// Check textdomain errors.
		checktextdomain: {
			options: {
				text_domain: "<%= options.text_domain %>",
				keywords: [
					"__:1,2d",
					"_e:1,2d",
					"_x:1,2c,3d",
					"esc_html__:1,2d",
					"esc_html_e:1,2d",
					"esc_html_x:1,2c,3d",
					"esc_attr__:1,2d",
					"esc_attr_e:1,2d",
					"esc_attr_x:1,2c,3d",
					"_ex:1,2c,3d",
					"_n:1,2,4d",
					"_nx:1,2,4c,5d",
					"_n_noop:1,2,3d",
					"_nx_noop:1,2,3c,4d",
				],
			},
			files: {
				src: [
					"**/*.php",
					"!.git/**",
					"!node_modules/**",
					"!vendor/**",
					"!tests/**",
					"!assets/**",
					"!deploy/**",
					"!build/**",
					"!dist/**",
					"!release/**",
				],
				expand: true,
			},
		},
		// Generate POT files.
		makepot: {
			target: {
				options: {
					type: "wp-plugin",
					domainPath: "languages",
					exclude: [
						".git/*",
						"deploy/.*",
						"node_modules/*",
						"vendor/*",
						"tests/*",
						"build/*",
						"release/*",
						"dist/*",
					],
					updateTimestamp: true,
					mainFile: "rishi-companion.php",
					potFilename: "<%= pkg.name %>.pot",
					potHeaders: {
						"report-msgid-bugs-to": "",
						"x-poedit-keywordslist": true,
						"language-team": "",
						Language: "en_US",
						"X-Poedit-SearchPath-0": "../../<%= pkg.name %>",
						"plural-forms": "nplurals=2; plural=(n != 1);",
						"Last-Translator": "Rishi Theme",
					},
				},
			},
		},
		svn_export: {
			dev: {
				options: {
					repository:
						"https://plugins.svn.wordpress.org/<%= pkg.name %>",
					output: "build/<%= pkg.name %>",
				},
			},
		},
		push_svn: {
			options: {
				remove: true,
				username: process.env.SVN_USERNAME,
				password: process.env.SVN_PASSWORD,
			},
			main: {
				src: "build/<%= pkg.name %>",
				dest: "https://plugins.svn.wordpress.org/<%= pkg.name %>",
				tmp: "build/make_svn",
			},
		},
		exec: {
			makepot: {
				cmd:
					"wp i18n make-pot ./ languages/rishi-companion.pot --ignore-domain",
			},
			production: {
				command: 'yarn build --production'
			}
		},
		// Replacements
		replace: {
			version: {
				src: ['readme.txt', 'style.css'],
				overwrite: true,
				replacements: [
					{
						from: /^((Stable tag|Version):\s([\d.])+)/gm,
						to: "$2: <%= pkg.version %>"
					}
				]
			},

		},
	});

	// Load tasks.
	grunt.loadNpmTasks("grunt-contrib-clean");
	grunt.loadNpmTasks("grunt-contrib-copy");
	grunt.loadNpmTasks("grunt-contrib-compress");
	grunt.loadNpmTasks("grunt-contrib-jshint");
	grunt.loadNpmTasks("grunt-contrib-cssmin");
	grunt.loadNpmTasks("grunt-contrib-watch");
	grunt.loadNpmTasks("grunt-contrib-uglify");
	grunt.loadNpmTasks("grunt-babel");
	grunt.loadNpmTasks("grunt-wp-i18n");
	grunt.loadNpmTasks("grunt-checktextdomain");
	grunt.loadNpmTasks("grunt-svn-export");
	grunt.loadNpmTasks("grunt-push-svn");
	grunt.loadNpmTasks("grunt-env");
	grunt.loadNpmTasks("grunt-exec");
	grunt.loadNpmTasks('grunt-text-replace');

	// Register default task.
	grunt.registerTask("default", ["dev"]);
	grunt.registerTask("i18n", ["addtextdomain", "makepot"]);
	grunt.registerTask("readme", ["wp_readme_to_markdown"]);

	// Register build task.
	grunt.registerTask("build", [
		"clean:all",
		"cssmin:build",
		"babel:build",
		"uglify:build",
	]);

	// Register dev task.
	grunt.registerTask("dev", ["build", "watch"]);

	grunt.registerTask("i18n", ["addtextdomain", "clean:pot", "makepot"]);

	grunt.registerTask("do_svn", [
		"svn_export",
		"copy:svn_trunk",
		"copy:svn_tag",
	]);

	// Register pre_release task.
	grunt.registerTask("pre_release", ["build", "do_svn"]);

	// Register pre_release task.
	grunt.registerTask("buildit", [
		"build",
		"copy:build",
		"compress",
	]);

	// Register release task.
	grunt.registerTask("release", [
		"compress:release",
		"env:release",
		"loadconstants",
		"push_svn",
	]);

	// Load constants.
	grunt.registerTask("loadconstants", "Load Constants", function () {
		console.log(process.env.SVN_USERNAME);

		grunt.config("SVN_USERNAME", process.env.SVN_USERNAME);
		grunt.config("SVN_PASSWORD", process.env.SVN_PASSWORD);
	});
};
