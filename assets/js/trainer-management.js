// Constants
const MODAL_IDS = {
    ADD: 'addTrainerModal',
    EDIT: 'editTrainerModal',
    VIEW: 'viewTrainerModal',
    DELETE: 'deleteModal'
  };
  
  // State management
  let currentTrainerToDelete = null;
  
  // Modal Management
  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'block';
      document.body.style.overflow = 'hidden';
    }
  }
  
  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
    }
  }
  
  // Event Handlers
  function handleModalClose(event) {
    if (event.target.classList.contains('modal')) {
      event.target.style.display = 'none';
      document.body.style.overflow = 'auto';
    }
  }
  
  function handleFormSubmit(event, formType) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    
    if (formType === 'add') {
      console.log('Adding new trainer:', data);
      closeModal(MODAL_IDS.ADD);
    } else if (formType === 'edit') {
      console.log('Updating trainer:', data);
      closeModal(MODAL_IDS.EDIT);
    }
    
    event.target.reset();
  }
  
  function confirmDelete() {
    if (currentTrainerToDelete) {
      console.log('Deleting trainer:', currentTrainerToDelete);
      closeModal(MODAL_IDS.DELETE);
      currentTrainerToDelete = null;
    }
  }
  
  // Trainer Data Management
  function loadTrainerDetails(trainerId) {
    // Simulated API data
    const trainerData = {
      name: 'John Doe',
      specialization: 'Strength Training',
      status: 'active',
      rating: '4.8',
      clients: '15',
      experience: '5 years',
      email: 'john@example.com',
      phone: '+1234567890',
      bio: 'Experienced trainer specializing in strength training and nutrition...',
      certifications: ['CPT Certified', 'Nutrition Specialist', 'CrossFit L1']
    };
  
    updateTrainerModal('viewTrainerModal', trainerData);
  }
  
  function loadTrainerForEdit(trainerId) {
    // Simulated API data
    const trainerData = {
      name: 'John Doe',
      email: 'john@example.com',
      phone: '+1234567890',
      specialization: 'strength',
      experience: '5',
      status: 'active',
      certifications: 'CPT Certified, Nutrition Specialist, CrossFit L1',
      bio: 'Experienced trainer specializing in strength training and nutrition...'
    };
  
    updateEditForm('editTrainerForm', trainerData);
  }
  
  // Filter Management
  function filterTrainers() {
    const filters = {
      search: document.getElementById('searchTrainer')?.value.toLowerCase() || '',
      specialization: document.getElementById('specializationFilter')?.value || '',
      status: document.getElementById('statusFilter')?.value || '',
      sort: document.getElementById('sortBy')?.value || 'name'
    };
  
    const trainerCards = document.querySelectorAll('.trainer-card');
    
    trainerCards.forEach(card => {
      const matches = {
        search: card.querySelector('h3')?.textContent.toLowerCase().includes(filters.search) ?? true,
        specialization: !filters.specialization || card.querySelector('.specialization')?.textContent.toLowerCase() === filters.specialization.toLowerCase(),
        status: !filters.status || card.querySelector('.trainer-status')?.textContent.toLowerCase() === filters.status.toLowerCase()
      };
  
      card.style.display = Object.values(matches).every(match => match) ? 'block' : 'none';
    });
  }
  
  // Utility Functions
  function updateTrainerModal(modalId, data) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
  
    const elements = {
      name: modal.querySelector('.trainer-name'),
      specialization: modal.querySelector('.trainer-specialization'),
      status: modal.querySelector('.trainer-status'),
      rating: modal.querySelector('.rating'),
      clients: modal.querySelector('.clients'),
      experience: modal.querySelector('.experience'),
      email: modal.querySelector('.trainer-email'),
      phone: modal.querySelector('.trainer-phone'),
      bio: modal.querySelector('.trainer-bio'),
      certifications: modal.querySelector('.certification-list')
    };
  
    Object.entries(elements).forEach(([key, element]) => {
      if (element && data[key]) {
        if (key === 'certifications') {
          element.innerHTML = Array.isArray(data[key]) 
            ? data[key].map(cert => `<span>${cert}</span>`).join('')
            : data[key];
        } else {
          element.textContent = data[key];
        }
      }
    });
  }
  
  function updateEditForm(formId, data) {
    const form = document.getElementById(formId);
    if (!form) return;
  
    Object.entries(data).forEach(([key, value]) => {
      const input = form.querySelector(`[name="${key}"]`);
      if (input) input.value = value;
    });
  }
  
  // Initialize
  document.addEventListener('DOMContentLoaded', () => {
    // Modal close handlers
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('click', handleModalClose);
    });
  
    document.querySelectorAll('.close-btn').forEach(btn => {
      btn.onclick = () => closeModal(btn.closest('.modal').id);
    });
  
    // Form handlers
    const addForm = document.getElementById('addTrainerForm');
    const editForm = document.getElementById('editTrainerForm');
  
    if (addForm) {
      addForm.addEventListener('submit', e => handleFormSubmit(e, 'add'));
    }
    if (editForm) {
      editForm.addEventListener('submit', e => handleFormSubmit(e, 'edit'));
    }
  
    // Filter handlers
    document.querySelectorAll('.trainer-filters select').forEach(select => {
      select.addEventListener('change', filterTrainers);
    });
  
    const searchInput = document.getElementById('searchTrainer');
    if (searchInput) {
      searchInput.addEventListener('input', filterTrainers);
    }
  });