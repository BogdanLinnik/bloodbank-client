pipeline {
    agent any
    
    triggers {
        githubPush()
    }
    
    stages {
        stage('Debug Variables') {
            steps {
                script {
                    println "=== Перевірка змінних ==="
                    println "AZURE_VM_USER: ${AZURE_VM_USER}"
                    println "AZURE_VM_HOST: ${AZURE_VM_HOST}"
                    println "=== Кінець перевірки ==="
                }
            }
        }
        
        stage('Fetch Code') {
            steps {
                sshagent(['azure-vm-ssh-key']) {
                    sh '''
                        echo "Спроба підключення як користувач: $AZURE_VM_USER"
                        echo "До хоста: $AZURE_VM_HOST"
                        
                        ssh -v $AZURE_VM_USER@$AZURE_VM_HOST "
                            echo 'SSH з'єднання успішне';
                            echo 'Поточна директорія:';
                            pwd;
                            cd /var/www/html && \
                            echo 'Перейшли до /var/www/html';
                            git fetch origin && \
                            echo 'Git fetch виконано';
                            git reset --hard origin/master && \
                            echo 'Git reset виконано';
                        "
                    '''
                }
            }
        }
        
        stage('Restart Apache') {
            steps {
                sshagent(['azure-vm-ssh-key']) {
                    sh '''
                        echo "Спроба перезапуску Apache..."
                        ssh $AZURE_VM_USER@$AZURE_VM_HOST "
                            echo 'Перевірка статусу Apache перед перезапуском:';
                            sudo systemctl status apache2;
                            echo 'Перезапуск Apache...';
                            sudo systemctl restart apache2;
                            echo 'Перевірка статусу Apache після перезапуску:';
                            sudo systemctl status apache2;
                        "
                    '''
                }
            }
        }
        
        stage('Health Check') {
            steps {
                script {
                    println "Виконання перевірки здоров'я для хоста: ${AZURE_VM_HOST}"
                    
                    def response = sh(
                        script: """
                            echo 'Виконання curl запиту...'
                            curl -v -s -o /dev/null -w '%{http_code}' http://$AZURE_VM_HOST
                        """,
                        returnStdout: true
                    ).trim()
                    
                    println "Отримано код відповіді: ${response}"
                    
                    if (response != "200") {
                        error "Перевірка здоров'я не вдалася! Сайт повернув HTTP ${response}"
                    } else {
                        println "Перевірка здоров'я успішна!"
                    }
                }
            }
        }
    }
    
    post {
        always {
            println "=== Фінальна інформація про виконання ==="
            println "BUILD_NUMBER: ${env.BUILD_NUMBER}"
            println "JOB_NAME: ${env.JOB_NAME}"
            println "WORKSPACE: ${env.WORKSPACE}"
        }
        success {
            println "Пайплайн успішно завершено!"
        }
        failure {
            println "Пайплайн завершився з помилкою!"
        }
    }
}
