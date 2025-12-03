import { useState, useEffect, useRef } from 'react';
import { 
  File as FileIcon,
  Image, 
  FileText, 
  Archive,
  Music,
  Video,
  Download,
  Trash2,
  LogOut,
  User,
  RefreshCw,
  Upload as UploadIcon,
  X,
  Check,
  ChevronDown,
  Globe
} from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { useLanguage } from '../context/LanguageContext';
import { filesAPI } from '../api/api';
import translations from '../i18n/translations';

const Dashboard = () => {
  const { user, logout } = useAuth();
  const { language, setLanguage } = useLanguage();
  const [files, setFiles] = useState([]);
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState([]);
  const [dragActive, setDragActive] = useState(false);
  const [sortBy, setSortBy] = useState('date-desc');
  const [showSortMenu, setShowSortMenu] = useState(false);
  const [showLanguageMenu, setShowLanguageMenu] = useState(false);
  const fileInputRef = useRef(null);

  const t = translations[language];

  useEffect(() => {
    fetchFiles();
  }, []);

  const fetchFiles = async () => {
    setLoading(true);
    try {
      const response = await filesAPI.getAll();
      setFiles(response.data);
    } catch (error) {
      console.error('Error fetching files:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFileUpload = async (selectedFiles) => {
    if (!selectedFiles || selectedFiles.length === 0) return;

    setUploading(true);
    const fileList = Array.from(selectedFiles);
    
    // Инициализируем прогресс для каждого файла
    setUploadProgress(fileList.map(f => ({ 
      name: f.name, 
      status: 'uploading' 
    })));

    try {
      if (fileList.length === 1) {
        await filesAPI.uploadSingle(fileList[0]);
      } else {
        await filesAPI.uploadMultiple(fileList);
      }
      
      setUploadProgress(fileList.map(f => ({ 
        name: f.name, 
        status: 'success' 
      })));
      
      setTimeout(() => {
        setUploadProgress([]);
        fetchFiles();
      }, 2000);
    } catch (error) {
      console.error('Upload error:', error);
      setUploadProgress(fileList.map(f => ({ 
        name: f.name, 
        status: 'error' 
      })));
      
      setTimeout(() => {
        setUploadProgress([]);
      }, 3000);
    } finally {
      setUploading(false);
    }
  };

  const handleDelete = async (id, fileName) => {
    if (!confirm(`Delete ${fileName}?`)) return;

    try {
      await filesAPI.delete(id);
      setFiles(files.filter(f => f.id !== id));
    } catch (error) {
      console.error('Delete error:', error);
      alert('Failed to delete file');
    }
  };

  const handleDownload = async (id) => {
    try {
      const response = await filesAPI.download(id);
      const { url } = response.data;

      // Открываем подписанный URL в новой вкладке
      window.open(url, '_blank');
    } catch (error) {
      console.error('Download error:', error);
      alert('Failed to download file');
    }
  };

  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true);
    } else if (e.type === "dragleave") {
      setDragActive(false);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      handleFileUpload(e.dataTransfer.files);
    }
  };

  const getFileIcon = (mimeType) => {
    if (mimeType.startsWith('image/')) return <Image className="w-5 h-5 text-blue-500" />;
    if (mimeType.startsWith('video/')) return <Video className="w-5 h-5 text-purple-500" />;
    if (mimeType.startsWith('audio/')) return <Music className="w-5 h-5 text-pink-500" />;
    if (mimeType.includes('pdf')) return <FileText className="w-5 h-5 text-red-500" />;
    if (mimeType.includes('zip') || mimeType.includes('rar')) return <Archive className="w-5 h-5 text-yellow-500" />;
    return <FileIcon className="w-5 h-5 text-gray-500" />;
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getSortedFiles = () => {
    const filesCopy = [...files];

    switch(sortBy) {
      case 'date-desc':
        return filesCopy.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      case 'date-asc':
        return filesCopy.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
      case 'size-desc':
        return filesCopy.sort((a, b) => b.size - a.size);
      case 'size-asc':
        return filesCopy.sort((a, b) => a.size - b.size);
      case 'name-asc':
        return filesCopy.sort((a, b) => a.name.localeCompare(b.name));
      case 'name-desc':
        return filesCopy.sort((a, b) => b.name.localeCompare(a.name));
      default:
        return filesCopy;
    }
  };

  const getSortLabel = () => {
    return t.sortOptions[sortBy];
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                <FileIcon className="w-6 h-6 text-white" />
              </div>
              <h1 className="text-xl font-bold text-gray-900">{t.header.title}</h1>
            </div>
            
            <div className="flex items-center space-x-4">
              {/* Language Selector */}
              <div className="relative">
                <button
                  onClick={() => setShowLanguageMenu(!showLanguageMenu)}
                  className="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors border border-gray-300 bg-white"
                >
                  <Globe className="w-4 h-4" />
                  <span className="font-medium">{language.toUpperCase()}</span>
                  <ChevronDown className="w-4 h-4" />
                </button>

                {showLanguageMenu && (
                  <div className="absolute right-0 mt-2 w-32 bg-white border border-gray-300 rounded-lg shadow-lg z-20">
                    {['en', 'ru', 'uz'].map(lang => (
                      <button
                        key={lang}
                        onClick={() => {
                          setLanguage(lang);
                          setShowLanguageMenu(false);
                        }}
                        className={`w-full text-left px-4 py-2 text-sm hover:bg-blue-50 transition-colors ${
                          language === lang 
                            ? 'bg-blue-100 text-blue-600 font-medium' 
                            : 'text-gray-700'
                        }`}
                      >
                        {lang === 'en' && 'English'}
                        {lang === 'ru' && 'Русский'}
                        {lang === 'uz' && 'O\'zbekcha'}
                      </button>
                    ))}
                  </div>
                )}
              </div>

              <div className="flex items-center space-x-2 text-sm text-gray-600">
                <User className="w-4 h-4" />
                <span className="font-medium">{user?.username}</span>
              </div>
              <button
                onClick={logout}
                className="flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                <LogOut className="w-4 h-4" />
                <span>{t.header.logout}</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Upload Area */}
        <div className="mb-8">
          <div
            className={`border-2 border-dashed rounded-2xl p-8 text-center transition-all ${
              dragActive 
                ? 'border-blue-500 bg-blue-50' 
                : 'border-gray-300 bg-white hover:border-blue-400 hover:bg-blue-50'
            }`}
            onDragEnter={handleDrag}
            onDragLeave={handleDrag}
            onDragOver={handleDrag}
            onDrop={handleDrop}
          >
            <input
              ref={fileInputRef}
              type="file"
              multiple
              onChange={(e) => handleFileUpload(e.target.files)}
              className="hidden"
            />
            
            <UploadIcon className="w-16 h-16 mx-auto mb-4 text-blue-500" />
            <h3 className="text-lg font-semibold text-gray-900 mb-2">
              {t.upload.title}
            </h3>
            <p className="text-sm text-gray-600 mb-4">
              {t.upload.description}
            </p>
            <button
              onClick={() => fileInputRef.current?.click()}
              disabled={uploading}
              className="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl"
            >
              {uploading ? t.upload.uploading : t.upload.button}
            </button>
          </div>

          {/* Upload Progress */}
          {uploadProgress.length > 0 && (
            <div className="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
              <h4 className="font-medium text-gray-900 mb-3">{t.upload.progress}</h4>
              <div className="space-y-2">
                {uploadProgress.map((file, index) => (
                  <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <div className="flex items-center space-x-2">
                      {file.status === 'uploading' && (
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                      )}
                      {file.status === 'success' && (
                        <Check className="w-4 h-4 text-green-500" />
                      )}
                      {file.status === 'error' && (
                        <X className="w-4 h-4 text-red-500" />
                      )}
                      <span className="text-sm text-gray-700">{file.name}</span>
                    </div>
                    <span className="text-xs text-gray-500">
                      {file.status === 'uploading' && t.upload.status.uploading}
                      {file.status === 'success' && t.upload.status.completed}
                      {file.status === 'error' && t.upload.status.failed}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Files Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h2 className="text-2xl font-bold text-gray-900">{t.files.title}</h2>
            <p className="text-sm text-gray-600 mt-1">
              {files.length} {files.length === 1 ? 'file' : 'files'} {t.files.stored}
            </p>
          </div>
          <div className="flex items-center space-x-3">
            {/* Sort Dropdown */}
            <div className="relative">
              <button
                onClick={() => setShowSortMenu(!showSortMenu)}
                className="flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors border border-gray-300 bg-white"
              >
                <span className="text-xs font-medium">{t.files.sort}: {getSortLabel()}</span>
                <ChevronDown className="w-4 h-4" />
              </button>

              {showSortMenu && (
                <div className="absolute right-0 mt-2 w-48 bg-white border border-gray-300 rounded-lg shadow-lg z-20">
                  {Object.entries(t.sortOptions).map(([key, label]) => (
                    <button
                      key={key}
                      onClick={() => {
                        setSortBy(key);
                        setShowSortMenu(false);
                      }}
                      className={`w-full text-left px-4 py-2 text-sm hover:bg-blue-50 transition-colors ${
                        sortBy === key 
                          ? 'bg-blue-100 text-blue-600 font-medium' 
                          : 'text-gray-700'
                      }`}
                    >
                      {label}
                    </button>
                  ))}
                </div>
              )}
            </div>

            <button
              onClick={fetchFiles}
              disabled={loading}
              className="flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
            >
              <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
              <span>{t.files.refresh}</span>
            </button>
          </div>
        </div>

        {/* Files Grid */}
        {loading ? (
          <div className="flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>
        ) : files.length === 0 ? (
          <div className="text-center py-20 bg-white rounded-2xl shadow-sm border border-gray-200">
            <FileIcon className="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">{t.files.noFiles}</h3>
            <p className="text-gray-600">{t.files.noFilesDesc}</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {getSortedFiles().map((file) => (
              <div
                key={file.id}
                className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-all group"
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center space-x-3">
                    {getFileIcon(file.mime_type)}
                    <div className="flex-1 min-w-0">
                      <h3 className="text-sm font-medium text-gray-900 truncate">
                        {file.name}
                      </h3>
                      <p className="text-xs text-gray-500">{formatFileSize(file.size)}</p>
                    </div>
                  </div>
                </div>

                <div className="flex items-center justify-between pt-3 border-t border-gray-100">
                  <span className="text-xs text-gray-500">
                    {formatDate(file.created_at)}
                  </span>
                  <div className="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      onClick={() => handleDownload(file.id)}
                      className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                      title="Download"
                    >
                      <Download className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => handleDelete(file.id, file.name)}
                      className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      title="Delete"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
};

export default Dashboard;

