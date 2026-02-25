import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useDashboardStore = defineStore('dashboard', () => {
    const loading = ref(false)
    const error = ref<string | null>(null)

    const overview = ref<any>(null)
    const performance = ref<any>(null)
    const insights = ref<any[]>([])

    const activeProjectId = ref<number | null>(null)
    const projects = ref<any[]>([])

    const fetchProjects = async () => {
        try {
            const { data } = await api.get('/projects')
            projects.value = data
            if (data.length > 0 && !activeProjectId.value) {
                setActiveProject(data[0].id)
            }
        } catch (err) {
            console.error('Falha ao buscar projetos', err)
        }
    }

    const setActiveProject = (projectId: number) => {
        activeProjectId.value = projectId
        fetchAllData(projectId)
    }

    const fetchAllData = async (projectId: number) => {
        if (!projectId) return

        loading.value = true
        error.value = null

        try {
            const [overviewRes, perfRes, insightsRes] = await Promise.all([
                api.get(`/dashboard/projects/${projectId}/overview`),
                api.get(`/dashboard/projects/${projectId}/performance`),
                api.get(`/dashboard/projects/${projectId}/insights`)
            ])

            overview.value = overviewRes.data
            performance.value = perfRes.data
            insights.value = insightsRes.data.insights

        } catch (err: any) {
            error.value = err?.response?.data?.error || 'Erro ao carregar dados do Dashboard.'
            console.error(err)
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        error,
        overview,
        performance,
        insights,
        activeProjectId,
        projects,
        setActiveProject,
        fetchAllData,
        fetchProjects
    }
})
