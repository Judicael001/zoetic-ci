/**
 * Widget d'évaluation du chatbot
 * À intégrer dans votre page avec le chatbot
 */

class ChatbotRatingWidget {
    constructor(options = {}) {
        this.options = {
            containerId: options.containerId || 'chatbot-rating-widget',
            apiEndpoint: options.apiEndpoint || '/admin/connect/save_chatbot_rating.php',
            ...options
        };
        this.init();
    }

    init() {
        this.container = document.getElementById(this.options.containerId);
        let wasCreated = false;
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = this.options.containerId;
            document.body.appendChild(this.container);
            wasCreated = true;
        }
        this.renderContainer(wasCreated);
    }

    renderContainer(wasCreated) {
        if (wasCreated) {
            this.container.style.display = 'none';
        }
        const container = this.container;
        container.innerHTML = `
            <style>
                .chatbot-rating-widget {
                    background: linear-gradient(135deg, #3f8f29 0%, #2d6a1f 100%);
                    border-radius: 16px;
                    padding: 16px;
                    color: white;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    box-shadow: 0 4px 12px rgba(63, 143, 41, 0.15);
                    margin-top: 12px;
                }

                .chatbot-rating-widget h4 {
                    margin: 0 0 10px 0;
                    font-size: 13px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    opacity: 0.9;
                }

                .chatbot-rating-widget p {
                    margin: 0 0 12px 0;
                    font-size: 14px;
                    line-height: 1.4;
                }

                .rating-buttons {
                    display: flex;
                    gap: 8px;
                    margin-bottom: 12px;
                    flex-wrap: wrap;
                }

                .rating-btn {
                    background: rgba(255, 255, 255, 0.2);
                    border: 2px solid rgba(255, 255, 255, 0.3);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 18px;
                    transition: all 0.2s ease;
                }

                .rating-btn:hover {
                    background: rgba(255, 255, 255, 0.3);
                    border-color: rgba(255, 255, 255, 0.6);
                    transform: scale(1.1);
                }

                .rating-btn.selected {
                    background: white;
                    color: #3f8f29;
                    border-color: white;
                }

                .helpful-buttons {
                    display: flex;
                    gap: 8px;
                    margin-bottom: 12px;
                }

                .helpful-btn {
                    flex: 1;
                    padding: 10px;
                    border: 2px solid rgba(255, 255, 255, 0.3);
                    background: rgba(255, 255, 255, 0.1);
                    color: white;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s ease;
                }

                .helpful-btn:hover {
                    background: rgba(255, 255, 255, 0.2);
                }

                .helpful-btn.selected {
                    background: white;
                    color: #3f8f29;
                }

                .feedback-textarea {
                    width: 100%;
                    padding: 10px;
                    border: 2px solid rgba(255, 255, 255, 0.3);
                    border-radius: 8px;
                    background: rgba(255, 255, 255, 0.1);
                    color: white;
                    font-family: inherit;
                    font-size: 13px;
                    margin-bottom: 10px;
                    resize: vertical;
                    min-height: 60px;
                }

                .feedback-textarea::placeholder {
                    color: rgba(255, 255, 255, 0.6);
                }

                .submit-btn {
                    background: white;
                    color: #3f8f29;
                    padding: 10px 16px;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    width: 100%;
                }

                .submit-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                }

                .submit-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }

                .success-message {
                    background: rgba(34, 197, 94, 0.2);
                    border-left: 4px solid #22c55e;
                    padding: 12px;
                    border-radius: 6px;
                    font-size: 13px;
                    display: none;
                }

                .success-message.show {
                    display: block;
                    animation: slideIn 0.3s ease;
                }

                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>

            <div class="chatbot-rating-widget">
                <h4>Vous avez trouvé cette réponse utile ?</h4>
                <p style="font-size: 12px;">Aidez-nous à améliorer votre expérience</p>

                <div class="helpful-buttons">
                    <button class="helpful-btn" data-helpful="yes" title="Oui, c'était utile">
                        👍 Oui
                    </button>
                    <button class="helpful-btn" data-helpful="no" title="Non, ce n'était pas utile">
                        👎 Non
                    </button>
                </div>

                <p style="font-size: 12px; margin-bottom: 8px;">Note la réponse :</p>
                <div class="rating-buttons">
                    <button class="rating-btn" data-rating="1" title="Très mauvais">1️⃣</button>
                    <button class="rating-btn" data-rating="2" title="Mauvais">2️⃣</button>
                    <button class="rating-btn" data-rating="3" title="Correct">3️⃣</button>
                    <button class="rating-btn" data-rating="4" title="Bon">4️⃣</button>
                    <button class="rating-btn" data-rating="5" title="Excellent">5️⃣</button>
                </div>

                <textarea class="feedback-textarea" placeholder="Commentaire optionnel... (max 500 caractères)"></textarea>

                <button class="submit-btn" id="submit-rating-btn">Envoyer mon évaluation</button>
                <div class="success-message">✓ Merci pour votre retour !</div>
            </div>
        `;
        
        this.attachEventListeners();
    }

    attachEventListeners() {
        const widget = this.container.querySelector('.chatbot-rating-widget');
        
        // Boutons d'utilité
        widget.querySelectorAll('.helpful-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                widget.querySelectorAll('.helpful-btn').forEach(b => b.classList.remove('selected'));
                e.target.classList.add('selected');
                this.data = this.data || {};
                this.data.helpful = e.target.dataset.helpful;
            });
        });

        // Boutons de note
        widget.querySelectorAll('.rating-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                widget.querySelectorAll('.rating-btn').forEach(b => b.classList.remove('selected'));
                e.target.classList.add('selected');
                this.data = this.data || {};
                this.data.rating = parseInt(e.target.dataset.rating);
            });
        });

        // Bouton d'envoi
        const submitBtn = widget.querySelector('#submit-rating-btn');
        const textarea = widget.querySelector('.feedback-textarea');

        submitBtn.addEventListener('click', async () => {
            if (!this.data || !this.data.rating) {
                alert('Veuillez donner une note');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Envoi...';

            try {
                const response = await fetch(this.options.apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        message_id: this.data.messageId || 0,
                        rating: this.data.rating,
                        helpful: this.data.helpful || '',
                        feedback: textarea.value
                    })
                });

                const result = await response.json();

                 if (result.success) {
                    widget.querySelector('.success-message').classList.add('show');
                    setTimeout(() => {
                        this.container.style.display = 'none';
                    }, 3000);
                } else {
                    alert(result.error || 'Erreur lors de l\'envoi');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Envoyer mon évaluation';
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Envoyer mon évaluation';
            }
        });

        this.data = {};
    }

     setMessageId(messageId) {
        this.data = this.data || {};
        this.data.messageId = messageId;
        this.data.rating = null;
        this.data.helpful = null;
        
        // Reset UI selection
        const widget = this.container.querySelector('.chatbot-rating-widget');
        if (widget) {
            widget.querySelectorAll('.helpful-btn').forEach(b => b.classList.remove('selected'));
            widget.querySelectorAll('.rating-btn').forEach(b => b.classList.remove('selected'));
            const textarea = widget.querySelector('.feedback-textarea');
            if (textarea) textarea.value = '';
            const successMsg = widget.querySelector('.success-message');
            if (successMsg) successMsg.classList.remove('show');
            const submitBtn = widget.querySelector('#submit-rating-btn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Envoyer mon évaluation';
            }
        }
        
        // Show container inside chatbot window
        this.container.style.display = 'block';
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    window.chatbotRatingWidget = new ChatbotRatingWidget();
});