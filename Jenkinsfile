pipeline {
    agent any
    
    environment {
        // Визначення глобальних змінних середовища
        CONFIG_FILE_ID = 'azure_config'
    }

    triggers {
        githubPush()
    }

    stages {
        stage('Load Configuration') {
            steps {
                configFileProvider([configFile(fileId: CONFIG_FILE_ID, variable: 'CONFIG_FILE')]) {
                    script {
                        // Завантаження змінних з конфіг файлу
                        def props = load CONFIG_FILE
                        env.AZURE_VM_USER = sh(script: "grep AZURE_VM_USER ${CONFIG_FILE} | cut -d'=' -f2", returnStdout: true).trim()
                        env.AZURE_VM_HOST = sh(script: "grep AZURE_VM_HOST ${CONFIG_FILE} | cut -d'=' -f2", returnStdout: true).trim()
                    }
                }
            }
        }

        stage('Fetch Code') {
            steps {
                sshagent(['server_key']) {
                    script {
                        sh "ssh ${AZURE_VM_USER}@${AZURE_VM_HOST} 'cd /var/www/html && git pull origin master'"
                    }
                }
            }
        }

        stage('Restart Apache') {
            steps {
                sshagent(['server_key']) {
                    script {
                        sh "ssh ${AZURE_VM_USER}@${AZURE_VM_HOST} 'sudo systemctl restart apache2'"
                    }
                }
            }
        }

        // Перевірка здоров'я
        stage('Health Check') {
            steps {
                script {
                    echo "Виконання перевірки здоров'я..."

                    def response = sh(script: "curl -s -o /dev/null -w %{http_code} http://${AZURE_VM_HOST}", returnStdout: true).trim()

                    echo "Відповідь від сервера: ${response}"

                    if (response == "200") {
                        echo "Перевірка здоров'я успішна! Сайт працює нормально."
                    } else {
                        error "Перевірка здоров'я не вдалася! Сайт повернув HTTP ${response}"
                    }
                }
            }
        }
    }

    // Пост-кроки виконання
    post {
        always {
            script {
                echo """
                    === Фінальна інформація про виконання ===
                    BUILD_NUMBER: ${env.BUILD_NUMBER}
                    JOB_NAME: ${env.JOB_NAME}
                    WORKSPACE: ${env.WORKSPACE}
                """.stripIndent()
            }
        }
        success {
            script {
                echo "Пайплайн успішно завершено!"
            }
        }
        failure {
            script {
                echo "Пайплайн завершився з помилкою!"
            }
        }
    }
}
