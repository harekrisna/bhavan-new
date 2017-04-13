module.exports = function(grunt) {
    grunt.initConfig({
        less: {
            development: {
                options: {
                    paths: ["www/css"],
                    yuicompress: true
                },
                files: {
                    "www/css/style.css": "www/less/style.less",
                    "www/css/audio-player.css": "www/less/audio-player.less"
                }
            }
        },
        watch: {
            files: "www/less/*.less",
            tasks: ["less"]
        }
    });
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('default', 'watch'); // registrace defaultní úlohy
};