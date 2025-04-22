pipeline {
    agent any

    triggers {
        githubPush()
    }

    stages {
        stage('Fetch Code') {
            steps {
                sshagent(['server_key']) {
                    sh '''
                        ssh $AZURE_VM_USER@$AZURE_VM_HOST "cd /var/www/html && \
                        git fetch origin && \
                        git reset --hard origin/master"
                    '''
                }
            }
        }

        stage('Restart Apache') {
            steps {
                sshagent(['server_key']) {
                    sh '''
                        ssh $AZURE_VM_USER@$AZURE_VM_HOST "sudo systemctl restart apache2"
                    '''
                }
            }
        }

        stage('Health Check') {
            steps {
                options {
                    timeout(time: 5, unit: 'MINUTES')
                }
                script {
                    def response = sh(
                        script: "curl -s -o /dev/null -w '%{http_code}' http://$AZURE_VM_HOST",
                        returnStdout: true
                    ).trim()

                    if (response != "200") {
                        error "Health check failed! Site returned HTTP ${response}"
                    }
                }
            }
        }
    }
}
