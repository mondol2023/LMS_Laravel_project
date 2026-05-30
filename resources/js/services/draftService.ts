// Draft Service for Auto-saving Exam Answers
interface DraftData {
    phone: string;
    exam_id: string;
    section: 'writing' | 'reading' | 'listening';
    part: string;
    question_key: string;
    answer: string | null;
}

interface BatchDraftData {
    phone: string;
    exam_id: string;
    section: 'writing' | 'reading' | 'listening';
    part: string;
    answers: Record<string, string | null>;
}

interface DraftResponse {
    success: boolean;
    message?: string;
    data?: any;
    errors?: any;
}

class DraftService {
    private baseUrl = '/api/drafts';
    private saveTimeout: NodeJS.Timeout | null = null;
    private pendingSaves: Map<string, DraftData> = new Map();

    /**
     * Save a single draft answer with debouncing
     */
    async saveDraft(data: DraftData, immediate = false): Promise<DraftResponse> {
        const key = this.getDraftKey(data);

        // Store in pending saves
        this.pendingSaves.set(key, data);

        if (immediate) {
            return this.processSave(data);
        }

        // Debounce save to avoid too many API calls
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        return new Promise((resolve) => {
            this.saveTimeout = setTimeout(async () => {
                const result = await this.processSave(data);
                resolve(result);
            }, 1000); // 1 second debounce
        });
    }

    /**
     * Save multiple answers at once
     */
    async saveBatchDrafts(data: BatchDraftData): Promise<DraftResponse> {
        try {
            const response = await fetch(`${this.baseUrl}/save-batch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken(),
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (!response.ok) {
                console.error('Batch draft save failed:', result);
                return { success: false, message: result.message, errors: result.errors };
            }

            console.log('Batch drafts saved successfully:', result);
            return result;
        } catch (error) {
            console.error('Batch draft save error:', error);
            return { success: false, message: 'Network error occurred' };
        }
    }

    /**
     * Get all drafts for phone and exam
     */
    async getDrafts(phone: string, examId: string): Promise<DraftResponse> {
        try {
            const url = new URL(`${window.location.origin}${this.baseUrl}/get`);
            url.searchParams.append('phone', phone);
            url.searchParams.append('exam_id', examId);

            const response = await fetch(url.toString());
            const result = await response.json();

            if (!response.ok) {
                console.error('Get drafts failed:', result);
                return { success: false, message: result.message, errors: result.errors };
            }

            return result;
        } catch (error) {
            console.error('Get drafts error:', error);
            return { success: false, message: 'Network error occurred' };
        }
    }

    /**
     * Get drafts for a specific section
     */
    async getSectionDrafts(phone: string, examId: string, section: string): Promise<DraftResponse> {
        try {
            const url = new URL(`${window.location.origin}${this.baseUrl}/get-section`);
            url.searchParams.append('phone', phone);
            url.searchParams.append('exam_id', examId);
            url.searchParams.append('section', section);

            const response = await fetch(url.toString());
            const result = await response.json();

            if (!response.ok) {
                console.error('Get section drafts failed:', result);
                return { success: false, message: result.message, errors: result.errors };
            }

            return result;
        } catch (error) {
            console.error('Get section drafts error:', error);
            return { success: false, message: 'Network error occurred' };
        }
    }

    /**
     * Clear all drafts for phone and exam
     */
    async clearDrafts(phone: string, examId: string): Promise<DraftResponse> {
        try {
            const response = await fetch(`${this.baseUrl}/clear`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken(),
                },
                body: JSON.stringify({ phone, exam_id: examId }),
            });

            const result = await response.json();

            if (!response.ok) {
                console.error('Clear drafts failed:', result);
                return { success: false, message: result.message, errors: result.errors };
            }

            return result;
        } catch (error) {
            console.error('Clear drafts error:', error);
            return { success: false, message: 'Network error occurred' };
        }
    }

    /**
     * Clear drafts for a specific section
     */
    async clearSectionDrafts(phone: string, examId: string, section: string): Promise<DraftResponse> {
        try {
            const response = await fetch(`${this.baseUrl}/clear-section`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken(),
                },
                body: JSON.stringify({ phone, exam_id: examId, section }),
            });

            const result = await response.json();

            if (!response.ok) {
                console.error('Clear section drafts failed:', result);
                return { success: false, message: result.message, errors: result.errors };
            }

            return result;
        } catch (error) {
            console.error('Clear section drafts error:', error);
            return { success: false, message: 'Network error occurred' };
        }
    }

    /**
     * Auto-save utility for components
     */
    createAutoSaver(phone: string, examId: string, section: 'writing' | 'reading' | 'listening', part: string) {
        return {
            save: (questionKey: string, answer: string | null) => {
                return this.saveDraft({
                    phone,
                    exam_id: examId,
                    section,
                    part,
                    question_key: questionKey,
                    answer,
                });
            },
            saveBatch: (answers: Record<string, string | null>) => {
                return this.saveBatchDrafts({
                    phone,
                    exam_id: examId,
                    section,
                    part,
                    answers,
                });
            },
            getDrafts: () => {
                return this.getSectionDrafts(phone, examId, section);
            },
            clearDrafts: () => {
                return this.clearSectionDrafts(phone, examId, section);
            },
        };
    }

    /**
     * Get user's phone number from various sources
     */
    getUserPhone(): string {
        // Try to get from localStorage first
        const storedPhone = localStorage.getItem('user_phone');
        if (storedPhone) {
            return storedPhone;
        }

        // Try to get from session storage
        const sessionPhone = sessionStorage.getItem('user_phone');
        if (sessionPhone) {
            return sessionPhone;
        }

        // Try to get from URL params
        const urlParams = new URLSearchParams(window.location.search);
        const phoneParam = urlParams.get('phone');
        if (phoneParam) {
            // Store for future use
            localStorage.setItem('user_phone', phoneParam);
            return phoneParam;
        }

        // Generate a temporary unique identifier if no phone is available
        const tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('user_phone', tempId);
        return tempId;
    }

    /**
     * Private helper methods
     */
    private async processSave(data: DraftData): Promise<DraftResponse> {
        try {
            const csrfToken = this.getCSRFToken();

            console.log('Saving draft:', {
                url: `${this.baseUrl}/save`,
                data: data,
                csrfToken: csrfToken ? 'present' : 'missing',
            });

            const response = await fetch(`${this.baseUrl}/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (!response.ok) {
                console.error('Draft save failed:', {
                    status: response.status,
                    statusText: response.statusText,
                    result: result,
                });

                if (response.status === 419) {
                    console.error('CSRF token mismatch - page may need refresh');
                }

                return { success: false, message: result.message, errors: result.errors };
            }

            console.log('Draft saved successfully:', result);
            return result;
        } catch (error) {
            console.error('Draft save error:', error);
            return { success: false, message: 'Network error occurred' };
        }
    }

    private getDraftKey(data: DraftData): string {
        return `${data.phone}_${data.exam_id}_${data.section}_${data.part}_${data.question_key}`;
    }

    private getCSRFToken(): string {
        const tokenElement = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
        const token = tokenElement?.content || '';

        if (!token) {
            console.warn('CSRF token not found in meta tag');
        }

        return token;
    }
}

// Export singleton instance
export const draftService = new DraftService();
export default draftService;
