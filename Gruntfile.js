module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		secret: grunt.file.readJSON('deploy_credentials.json'),

		clean: {
			build: {
				src: 'dist/'
			}
		},

		copy: {
			main: {
				expand: true,
				cwd: 'src',
				src: '**',
				dest: 'dist/',
			},
		},

		watch: {
			options: {
				livereload: true,
				spawn: true
			},

			files: {
				files: ['src/**'],
				tasks: ['newer:copy']
			}
		},

		sshconfig: {
			production: {
				host: '<%= secret.host %>',
				username: '<%= secret.username %>',
				password: '<%= secret.password %>'
			}
		},

		sftp: {
			deploy: {
				files: {
					'./': ['dist/**']
				},
				options: {
					path: '/www/',

					srcBasePath: 'dist/',

					showProgress: true,
					createDirectories: true
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-newer');
	grunt.loadNpmTasks('grunt-ssh');

	grunt.option('config', 'production');

	grunt.registerTask('build', ['clean', 'copy']);
	grunt.registerTask('deploy', ['build', 'sftp:deploy']);
	grunt.registerTask('default', ['build', 'watch']);

};
