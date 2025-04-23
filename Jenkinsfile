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
        // Завантаження конфігурації
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

        // Відлагодження змінних
        stage('Debug Variables') {
            steps {
                script {
                    echo """
                        === Перевірка змінних ===
                        AZURE_VM_USER: ${AZURE_VM_USER}
                        AZURE_VM_HOST: ${AZURE_VM_HOST}
                        === Кінець перевірки ===
                    """.stripIndent()
                }
            }
        }

        // Отримання коду
        stage('Fetch Code') {
            steps {
                sshagent(['server_key']) {
                    script {
                        def sshCmd = """
                            echo "Спроба підключення як користувач: ${AZURE_VM_USER}"
                            echo "До хоста: ${AZURE_VM_HOST}"

                            ssh -v ${AZURE_VM_USER}@${AZURE_VM_HOST} "
                                echo 'SSH з\\'єднання успішне' && \
                                echo 'Поточна директорія:' && \
                                pwd && \
                                cd /var/www/html && \
                                echo 'Перейшли до /var/www/html' && \
                                git fetch origin && \
                                echo 'Git fetch виконано' && \
                                git reset --hard origin/master && \
                                echo 'Git reset виконано'
                            "
                        """
                        sh sshCmd
                    }
                }
            }
        }

        // Перезапуск Apache
        stage('Restart Apache') {
            steps {
                sshagent(['server_key']) {
                    script {
                        def apacheCmd = """
                            echo "Спроба перезапуску Apache..."
                            ssh ${AZURE_VM_USER}@${AZURE_VM_HOST} "
                                echo 'Перевірка статусу Apache перед перезапуском:' && \
                                sudo systemctl status apache2 && \
                                echo 'Перезапуск Apache...' && \
                                sudo systemctl restart apache2 && \
                                echo 'Перевірка статусу Apache після перезапуску:' && \
                                sudo systemctl status apache2
                            "
                        """
                        sh apacheCmd
                    }
                }
            }
        }

        // Перевірка здоров'я
        stage('Health Check') {
            steps {
                script {
                    echo "Виконання перевірки здоров'я для хоста: ${AZURE_VM_HOST}"

                    def response = sh(
                        script: """
                            echo 'Виконання curl запиту...'
                            curl -v -s -o /dev/null -w '%{http_code}' http://${AZURE_VM_HOST}
                        """,
                        returnStdout: true
                    ).trim()

                    echo "Отримано код відповіді: ${response}"

                    if (response != "200") {
                        error "Перевірка здоров'я не вдалася! Сайт повернув HTTP ${response}"
                    } else {
                        echo "Перевірка здоров'я успішна!"
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
