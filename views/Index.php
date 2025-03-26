<div id="subscriptions-app" class="p-4">
    <div class="container" style="position: relative;">
        <div class="panel">
            <div class="tbl-ctrls">
                <div class="panel-heading entry-pannel-heading">
                    <div class="title-bar">
                        <h3 class="title-bar__title">Subscriptions</h3>
                    </div>
                </div>
                <div class="filter-search-bar">
                    <!-- All filters (not including search input) are contained within 'filter-search-bar__filter-row' -->
                    <div class="filter-search-bar__filter-row">
                        <div class="filter-search-bar__item ">
                            <select v-model="filters.member" @change="fetchData" class="border p-2">
                                <option value="">All Members</option>
                                <option v-for="member in members" :key="member.id" :value="member.id">
                                    {{ member.name }}
                                </option>
                            </select>
                        </div>
                        <div class="filter-search-bar__item ">
                            <select v-model="filters.status" @change="fetchData" class="border p-2">
                                <option value="">All Statuses</option>
                                <option v-for="status in statuses" :key="status" :value="status">
                                    {{ status }}
                                </option>
                            </select>
                        </div>
                        <div class="filter-search-bar__item ">
                            <input
                                type="text"
                                v-model="searchInput"
                                placeholder="Search"
                                class="border p-2 flex-1"
                            />
                        </div>
                    </div>
                </div>
                <div class="table-responsive table-responsive--collapsible">
                    <table cellspacing="0">
                        <thead>
                            <tr class="app-listing__row app-listing__row--head">
                                <th class="column-sort-header">Sub ID</th>
                                <th class="column-sort-header">Last Rebill</th>
                                <th class="column-sort-header">Next Rebill</th>
                                <th class="column-sort-header">Name</th>
                                <th class="column-sort-header">Member</th>
                                <th class="column-sort-header">Order</th>
                                <th class="column-sort-header">Status</th>
                                <th class="column-sort-header">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sub in subscriptions" :key="sub.id" class="app-listing__row">
                                <td class="p-2">
                                    <a :href="sub.manage_url" target="_blank">
                                        {{ sub.id }}
                                    </a>
                                </td>
                                <td class="p-2">{{ sub.last_rebill }}</td>
                                <td class="p-2">{{ sub.next_rebill }}</td>
                                <td class="p-2">{{ sub.name }}</td>
                                <td class="p-2">{{ sub.member }}</td>
                                <td class="p-2">
                                    <a :href="sub.order_url" target="_blank">
                                        {{ sub.order }}
                                    </a>
                                </td>
                                <td class="p-2">{{ sub.status }}</td>
                                <td class="p-2">
                                    <a :href="sub.manage_url" class="text-blue-500 underline">Manage</a>
                                </td>
                            </tr>
                            <tr v-if="!subscriptions || !subscriptions.length">
                                <td colspan="8" class="p-4 text-center">No subscriptions found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination" style="margin-top: 2rem;margin-bottom: 2rem;">
                    <button @click="prevPage" :disabled="page === 1" class="px-4 py-2 border">
                        Prev
                    </button>
                    <span>Page {{ page }}</span>
                    <button @click="nextPage" :disabled="!hasMore" class="px-4 py-2 border">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
const { createApp, ref, watch } = Vue;

createApp({
    setup() {
        const actionUrl = '/?ACT=<?=$action_id?>'; // Replace with your actual EE action URL

        const members = ref(<?=json_encode($members)?>);

        const statuses = ref(<?=json_encode($statuses)?>); // Add your statuses

        const filters = ref({
            member: '',
            status: '',
        });

        const searchInput = ref('');
        const searchQuery = ref('');
        const subscriptions = ref([]);
        const page = ref(1);
        const limit = ref(10);
        const hasMore = ref(false);
        let debounceTimer = null;

        const fetchData = async () => {
            const params = new URLSearchParams({
                csrf_token: '<?=CSRF_TOKEN?>',
                page: page.value,
                limit: limit.value,
                search: searchQuery.value,
                member: filters.value.member,
                status: filters.value.status,
            });

            const response = await fetch(`${actionUrl}&${params}`);
            const data = await response.json();

            subscriptions.value = data.subscriptions || [];
            hasMore.value = data.has_more || false;
        };

        const nextPage = () => {
            page.value++;
            fetchData();
        };

        const prevPage = () => {
            if (page.value > 1) {
                page.value--;
                fetchData();
            }
        };

        watch(searchInput, (val) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                searchQuery.value = val;
                page.value = 1;
                fetchData();
            }, 300);
        });

        // Initial load
        fetchData();

        return {
            members,
            statuses,
            filters,
            searchInput,
            subscriptions,
            page,
            hasMore,
            nextPage,
            prevPage,
            fetchData,
        };
    },
}).mount('#subscriptions-app');
</script>
